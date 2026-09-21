<?php

use App\Enums\MovementAction;
use App\Models\Contract;
use App\Models\ContractMovement;
use App\Models\User;

test('creating a contract records who created it', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $contract = Contract::factory()->create(['reference' => 'CT-2026-0001']);

    $movement = $contract->movements()->sole();

    expect($movement->action)->toBe(MovementAction::Created);
    expect($movement->user_id)->toBe($user->id);
    expect($movement->contract_reference)->toBe('CT-2026-0001');
    expect($movement->changes)->toBeNull();
});

test('editing a contract records the old and new value of each changed field', function () {
    $contract = Contract::factory()->create(['expected_amount' => 12000, 'title' => 'Obras de reforma']);
    $editor = User::factory()->create();

    $this->actingAs($editor);
    $contract->update(['expected_amount' => 15500, 'title' => 'Obras de reforma y accesibilidad']);

    $movement = $contract->movements()->where('action', MovementAction::Updated)->sole();

    expect($movement->user_id)->toBe($editor->id);
    expect($movement->changes)->toEqual([
        'expected_amount' => ['old' => '12000.00', 'new' => '15500.00'],
        'title' => ['old' => 'Obras de reforma', 'new' => 'Obras de reforma y accesibilidad'],
    ]);
});

test('completing a contract records the filled fields and the formalization date', function () {
    $this->travelTo('2026-09-19 10:00:00');
    $contract = Contract::factory()->create();

    $contract->update([
        'amount' => 15000,
        'start_date' => '2026-10-01',
        'end_date' => '2028-09-30',
        'responsible' => 'Acme S.L.',
    ]);

    $movement = $contract->movements()->where('action', MovementAction::Updated)->sole();

    expect($movement->changes)->toEqual([
        'amount' => ['old' => null, 'new' => '15000.00'],
        'start_date' => ['old' => null, 'new' => '2026-10-01'],
        'end_date' => ['old' => null, 'new' => '2028-09-30'],
        'responsible' => ['old' => null, 'new' => 'Acme S.L.'],
        'formalized_at' => ['old' => null, 'new' => '2026-09-19'],
    ]);
});

test('updating only the reminder timestamp does not record a movement', function () {
    $contract = Contract::factory()->create();

    $contract->update(['last_reminder_sent_at' => now()]);

    expect($contract->movements()->where('action', MovementAction::Updated)->exists())->toBeFalse();
});

test('a change made without an authenticated user is attributed to the system', function () {
    $contract = Contract::factory()->create();

    $contract->update(['title' => 'Título corregido']);

    $movement = $contract->movements()->where('action', MovementAction::Updated)->sole();

    expect($movement->user_id)->toBeNull();
});

test('deleting a contract soft deletes it and records who deleted it', function () {
    $contract = Contract::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);
    $contract->delete();

    $movement = $contract->movements()->where('action', MovementAction::Deleted)->sole();

    expect($movement->user_id)->toBe($user->id);
    expect(Contract::find($contract->id))->toBeNull();
    expect(Contract::withTrashed()->find($contract->id)->trashed())->toBeTrue();
});

test('restoring a contract records a single restored movement', function () {
    $contract = Contract::factory()->create();
    $contract->delete();

    $contract->restore();

    expect($contract->movements()->pluck('action')->all())->toBe([
        MovementAction::Created,
        MovementAction::Deleted,
        MovementAction::Restored,
    ]);
    expect($contract->movements()->where('action', MovementAction::Restored)->sole()->changes)->toBeNull();
});

test('restoring a contract past its deadline starts a new formalization period', function () {
    $this->travelTo('2026-09-19 10:00:00');
    $contract = Contract::factory()->create(['formalization_deadline' => '2026-09-01']);
    $contract->delete();

    $contract->restore();

    $movement = $contract->movements()->where('action', MovementAction::Restored)->sole();

    expect($contract->fresh()->formalization_deadline->toDateString())->toBe('2027-01-19');
    expect($movement->changes)->toEqual([
        'formalization_deadline' => ['old' => '2026-09-01', 'new' => '2027-01-19'],
    ]);
    expect($contract->movements()->where('action', MovementAction::Updated)->exists())->toBeFalse();
});

test('restoring a formalized contract keeps its deadline', function () {
    $this->travelTo('2026-09-19 10:00:00');
    $contract = Contract::factory()->formalized()->create(['formalization_deadline' => '2026-09-01']);
    $contract->delete();

    $contract->restore();

    expect($contract->fresh()->formalization_deadline->toDateString())->toBe('2026-09-01');
});

test('permanently deleting a contract keeps its movements with the reference', function () {
    $contract = Contract::factory()->create(['reference' => 'CT-2026-0002']);
    $contract->delete();

    $contract->forceDelete();

    $movements = ContractMovement::where('contract_reference', 'CT-2026-0002')->get();

    expect(Contract::withTrashed()->find($contract->id))->toBeNull();
    expect($movements->pluck('action')->all())->toBe([
        MovementAction::Created,
        MovementAction::Deleted,
        MovementAction::ForceDeleted,
    ]);
    expect($movements->pluck('contract_id')->filter())->toBeEmpty();
});
