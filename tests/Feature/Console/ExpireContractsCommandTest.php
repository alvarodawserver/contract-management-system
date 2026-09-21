<?php

use App\Enums\MovementAction;
use App\Models\Contract;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(fn () => $this->travelTo('2026-09-19 10:00:00'));

test('soft deletes unformalized contracts whose deadline has passed', function () {
    $expired = Contract::factory()->create(['formalization_deadline' => '2026-09-18']);

    $this->artisan('contracts:expire')->assertSuccessful();

    expect($expired->fresh()->trashed())->toBeTrue();
});

test('leaves contracts that are still within their deadline', function (string $deadline) {
    $contract = Contract::factory()->create(['formalization_deadline' => $deadline]);

    $this->artisan('contracts:expire')->assertSuccessful();

    expect($contract->fresh()->trashed())->toBeFalse();
})->with([
    'deadline is today' => '2026-09-19',
    'deadline in the future' => '2026-12-01',
]);

test('leaves contracts without a deadline', function () {
    $contract = Contract::factory()->create();
    $contract->update(['formalization_deadline' => null]);

    $this->artisan('contracts:expire')->assertSuccessful();

    expect($contract->fresh()->trashed())->toBeFalse();
});

test('leaves formalized contracts even when their deadline has passed', function () {
    $contract = Contract::factory()->formalized()->create(['formalization_deadline' => '2026-09-01']);

    $this->artisan('contracts:expire')->assertSuccessful();

    expect($contract->fresh()->trashed())->toBeFalse();
});

test('records the expiration as a deletion made by the system', function () {
    $expired = Contract::factory()->create(['formalization_deadline' => '2026-09-18']);

    $this->artisan('contracts:expire');

    $movement = $expired->movements()->where('action', MovementAction::Deleted)->sole();

    expect($movement->user_id)->toBeNull();
});

test('an expired contract that is restored gets a new formalization period', function () {
    $expired = Contract::factory()->create(['formalization_deadline' => '2026-09-18']);
    $this->artisan('contracts:expire');

    Contract::onlyTrashed()->sole()->restore();

    expect($expired->fresh()->formalization_deadline->toDateString())->toBe('2027-01-19');
    $this->artisan('contracts:expire');
    expect($expired->fresh()->trashed())->toBeFalse();
});

test('is scheduled to run every day at 00:10', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'contracts:expire'));

    expect($event->expression)->toBe('10 0 * * *');
});
