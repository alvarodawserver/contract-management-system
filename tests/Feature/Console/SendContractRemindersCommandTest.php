<?php

use App\Models\Contract;
use App\Models\Department;
use App\Models\User;
use App\Notifications\ContractFormalizationReminder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo('2026-09-19 10:00:00');
    Notification::fake();
});

test('reminds the creator of an unformalized contract with time left', function () {
    $creator = User::factory()->create();
    Contract::factory()->for($creator, 'creator')->create(['formalization_deadline' => '2026-12-19']);

    $this->artisan('contracts:send-reminders')->assertSuccessful();

    Notification::assertSentTo($creator, ContractFormalizationReminder::class);
    Notification::assertCount(1);
});

test('sends the last reminder when exactly one week is left and none afterwards', function (string $deadline, bool $reminded) {
    $creator = User::factory()->create();
    Contract::factory()->for($creator, 'creator')->create(['formalization_deadline' => $deadline]);

    $this->artisan('contracts:send-reminders');

    $reminded
        ? Notification::assertSentTo($creator, ContractFormalizationReminder::class)
        : Notification::assertNothingSent();
})->with([
    'a week left' => ['2026-09-26', true],
    'less than a week left' => ['2026-09-25', false],
    'deadline today' => ['2026-09-19', false],
    'deadline already passed' => ['2026-09-10', false],
]);

test('does not remind formalized, deleted or deadline-less contracts', function () {
    Contract::factory()->formalized()->create(['formalization_deadline' => '2026-12-19']);
    Contract::factory()->create(['formalization_deadline' => '2026-12-19'])->delete();
    Contract::factory()->create()->update(['formalization_deadline' => null]);

    $this->artisan('contracts:send-reminders');

    Notification::assertNothingSent();
});

test('does not remind a contract twice within the same week', function () {
    Contract::factory()->create(['formalization_deadline' => '2026-12-19']);

    $this->artisan('contracts:send-reminders');
    $this->artisan('contracts:send-reminders');
    Notification::assertCount(1);

    $this->travelTo('2026-09-26 08:00:00');
    $this->artisan('contracts:send-reminders');
    Notification::assertCount(2);
});

test('records when the contract was reminded without logging it as a change', function () {
    $contract = Contract::factory()->create(['formalization_deadline' => '2026-12-19']);

    $this->artisan('contracts:send-reminders');

    expect($contract->fresh()->last_reminder_sent_at->toDateTimeString())->toBe('2026-09-19 10:00:00');
    expect($contract->movements()->count())->toBe(1);
});

test('the reminder tells the reference, the deadline, the time left and the missing data', function () {
    $department = Department::factory()->create(['name' => 'Urbanismo']);
    $creator = User::factory()->create(['name' => 'Carmen Ruiz']);
    $contract = Contract::factory()->for($department)->for($creator, 'creator')->create([
        'reference' => 'CT-2026-0007',
        'title' => 'Obras de reforma',
        'amount' => 15000,
        'responsible' => 'Acme S.L.',
        'formalization_deadline' => '2026-12-19',
    ]);

    $mail = (new ContractFormalizationReminder($contract))->toMail($creator);
    $text = collect([$mail->subject, ...$mail->introLines])->implode("\n");

    expect($text)
        ->toContain('CT-2026-0007')
        ->toContain('Urbanismo')
        ->toContain('19/12/2026')
        ->toContain('3 meses')
        ->toContain('fecha de inicio, fecha de fin')
        ->not->toContain('importe final');
});

test('is scheduled every Monday at 08:00', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'contracts:send-reminders'));

    expect($event->expression)->toBe('0 8 * * 1');
});
