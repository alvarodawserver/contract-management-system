<?php

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;

const COMPLETE = ['amount' => 12000, 'start_date' => '2026-10-01', 'end_date' => '2027-09-30', 'responsible' => 'Acme S.L.'];

test('derives the status from the filled fields and the formalization deadline', function (array $attributes, ContractStatus $expected) {
    $this->travelTo('2026-09-19 10:00:00');

    expect((new Contract($attributes))->status)->toBe($expected);
})->with([
    'all fields filled' => [COMPLETE + ['formalization_deadline' => '2026-12-01'], ContractStatus::Formalized],
    'all fields filled past the deadline' => [COMPLETE + ['formalization_deadline' => '2026-09-01'], ContractStatus::Formalized],
    'a field missing within the deadline' => [['amount' => 12000, 'formalization_deadline' => '2026-12-01'], ContractStatus::Pending],
    'a field missing on the deadline day' => [['amount' => 12000, 'formalization_deadline' => '2026-09-19'], ContractStatus::Pending],
    'a field missing past the deadline' => [['amount' => 12000, 'formalization_deadline' => '2026-09-18'], ContractStatus::Lapsed],
    'a field missing without a deadline' => [['amount' => 12000, 'formalization_deadline' => null], ContractStatus::Pending],
]);

test('scopes split contracts by their derived status', function () {
    $this->travelTo('2026-09-19 10:00:00');
    $formalized = Contract::factory()->formalized()->create();
    $pending = Contract::factory()->create();
    $lapsed = Contract::factory()->create(['formalization_deadline' => '2026-09-01']);

    expect(Contract::formalized()->sole()->is($formalized))->toBeTrue();
    expect(Contract::pending()->sole()->is($pending))->toBeTrue();
    expect(Contract::lapsed()->sole()->is($lapsed))->toBeTrue();
});

test('visibleTo keeps every contract for an admin and only the department ones for anyone else', function () {
    $employee = User::factory()->delegatedEmployee()->create();
    $admin = User::factory()->admin()->create();
    $own = Contract::factory()->create(['department_id' => $employee->departmentId()]);
    Contract::factory()->create();

    expect(Contract::visibleTo($employee)->pluck('id')->all())->toBe([$own->id]);
    expect(Contract::visibleTo($admin)->count())->toBe(2);
});

test('visibleTo hides every contract from a user without a department', function () {
    Contract::factory()->create();

    expect(Contract::visibleTo(User::factory()->create())->count())->toBe(0);
});

test('matching finds contracts by title or reference ignoring case, and not by responsible', function () {
    $byTitle = Contract::factory()->create(['title' => 'Renovación del alumbrado']);
    $byReference = Contract::factory()->create(['title' => 'Limpieza', 'reference' => 'CT-2026-0042']);
    Contract::factory()->create(['title' => 'Mobiliario', 'responsible' => 'Alumbrados S.A.']);

    expect(Contract::matching('ALUMBRADO')->pluck('id')->all())->toBe([$byTitle->id]);
    expect(Contract::matching('ct-2026-0042')->pluck('id')->all())->toBe([$byReference->id]);
});

test('overlapping keeps the contracts whose term touches the period', function (?string $from, ?string $to, int $expected) {
    Contract::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'expected_date' => null]);
    Contract::factory()->create(['start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'expected_date' => null]);
    Contract::factory()->create(['start_date' => null, 'end_date' => null, 'expected_date' => '2026-03-15']);
    Contract::factory()->create(['start_date' => null, 'end_date' => null, 'expected_date' => null]);

    $count = Contract::overlapping($from ? now()->parse($from) : null, $to ? now()->parse($to) : null)->count();

    expect($count)->toBe($expected);
})->with([
    'period across the first two contracts' => ['2026-05-01', '2026-08-31', 2],
    'only a start: the ones that end after it' => ['2026-09-01', null, 1],
    'only an end: the ones that start before it' => [null, '2026-02-01', 1],
    'no period keeps everything, even without dates' => [null, null, 4],
    'expected date stands in while there are no dates' => ['2026-03-01', '2026-03-31', 2],
]);

test('calculates the duration counting the end date', function (string $start, string $end, array $expected) {
    expect((new Contract(['start_date' => $start, 'end_date' => $end]))->duration)->toBe($expected);
})->with([
    'whole year' => ['2026-01-01', '2026-12-31', ['years' => 1, 'months' => 0, 'days' => 0]],
    'year and three months' => ['2026-01-01', '2027-03-31', ['years' => 1, 'months' => 3, 'days' => 0]],
    'less than a month' => ['2026-01-15', '2026-02-10', ['years' => 0, 'months' => 0, 'days' => 27]],
]);

test('has no duration while a date is missing or the range is inverted', function (array $attributes) {
    expect((new Contract($attributes))->duration)->toBeNull();
})->with([
    'no start date' => [['end_date' => '2026-12-31']],
    'no end date' => [['start_date' => '2026-01-01']],
    'end before start' => [['start_date' => '2026-12-31', 'end_date' => '2026-01-01']],
]);

test('sets the formalization date when the contract becomes complete and clears it when it stops being so', function () {
    $this->travelTo('2026-09-19 10:00:00');
    $contract = Contract::factory()->create();
    expect($contract->formalized_at)->toBeNull();

    $contract->update(COMPLETE);
    expect($contract->fresh()->formalized_at->toDateString())->toBe('2026-09-19');

    $this->travelTo('2026-09-25 10:00:00');
    $contract->update(['title' => 'Otro título']);
    expect($contract->fresh()->formalized_at->toDateString())->toBe('2026-09-19');

    $contract->update(['end_date' => null]);
    expect($contract->fresh()->formalized_at)->toBeNull();
});

test('gives a new contract the configured formalization period', function (string $today, int $months, string $expected) {
    config(['contracts.formalization_months' => $months]);
    $this->travelTo("$today 10:00:00");

    $contract = Contract::factory()->create();

    expect($contract->formalization_deadline->toDateString())->toBe($expected);
})->with([
    'default period' => ['2026-09-19', 4, '2027-01-19'],
    'custom period' => ['2026-09-19', 6, '2027-03-19'],
    'end of month does not overflow' => ['2026-10-31', 4, '2027-02-28'],
]);

test('keeps a formalization deadline set explicitly', function () {
    $contract = Contract::factory()->create(['formalization_deadline' => '2027-06-30']);

    expect($contract->formalization_deadline->toDateString())->toBe('2027-06-30');
});

test('a deadline cleared after creation stays empty', function () {
    $contract = Contract::factory()->create();

    $contract->update(['formalization_deadline' => null]);

    expect($contract->fresh()->formalization_deadline)->toBeNull();
});

test('generates sequential references per year', function () {
    $this->travelTo('2026-09-19 10:00:00');

    $first = Contract::factory()->create();
    $second = Contract::factory()->create();

    expect($first->reference)->toBe('CT-2026-0001');
    expect($second->reference)->toBe('CT-2026-0002');
});

test('does not reuse the reference of a permanently deleted contract', function () {
    $this->travelTo('2026-09-19 10:00:00');
    Contract::factory()->create();
    Contract::factory()->create()->forceDelete();
    Contract::factory()->create();

    expect(Contract::pluck('reference')->all())->toBe(['CT-2026-0001', 'CT-2026-0003']);
});
