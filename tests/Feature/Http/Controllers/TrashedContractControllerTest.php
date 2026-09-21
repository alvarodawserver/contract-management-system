<?php

use App\Models\Contract;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    test('forbids a delegated employee', function () {
        $this->actingAs(User::factory()->delegatedEmployee()->create())
            ->get(route('contracts.trash.index'))
            ->assertForbidden();
    });

    test('shows a head only the deleted contracts of their department', function () {
        $head = User::factory()->departmentHead()->create();
        Contract::factory()->count(2)->create(['department_id' => $head->departmentId()])->each->delete();
        Contract::factory()->create()->delete();
        Contract::factory()->create(['department_id' => $head->departmentId()]);

        $this->actingAs($head)->get(route('contracts.trash.index'))
            ->assertInertia(fn (Assert $page) => $page->component('contracts/trash')->has('contracts.data', 2));
    });

    test('shows an admin every deleted contract', function () {
        $admin = User::factory()->admin()->create();
        Contract::factory()->count(3)->create()->each->delete();

        $this->actingAs($admin)->get(route('contracts.trash.index'))
            ->assertInertia(fn (Assert $page) => $page->has('contracts.data', 3));
    });

    test('flags a contract deleted after missing its deadline as lapsed', function () {
        $this->travelTo('2026-09-19 10:00:00');
        $admin = User::factory()->admin()->create();
        Contract::factory()->create(['formalization_deadline' => '2026-09-01'])->delete();

        $this->actingAs($admin)->get(route('contracts.trash.index'))
            ->assertInertia(fn (Assert $page) => $page->where('contracts.data.0.status', 'lapsed'));
    });
});

describe('restore', function () {
    test('lets a head restore a deleted contract of their department', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);
        $contract->delete();

        $this->actingAs($head)->patch(route('contracts.restore', $contract))
            ->assertRedirect(route('contracts.show', $contract));

        expect($contract->fresh()->trashed())->toBeFalse();
    });

    test('forbids a delegated employee', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId(), 'created_by' => $employee->id]);
        $contract->delete();

        $this->actingAs($employee)->patch(route('contracts.restore', $contract))->assertForbidden();

        expect(Contract::withTrashed()->find($contract->id)->trashed())->toBeTrue();
    });

    test('forbids the head of another department', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create();
        $contract->delete();

        $this->actingAs($head)->patch(route('contracts.restore', $contract))->assertForbidden();
    });
});

describe('force destroy', function () {
    test('lets an admin delete a contract permanently', function () {
        $admin = User::factory()->admin()->create();
        $contract = Contract::factory()->create();
        $contract->delete();

        $this->actingAs($admin)->delete(route('contracts.force-destroy', $contract))
            ->assertRedirect(route('contracts.trash.index'));

        expect(Contract::withTrashed()->find($contract->id))->toBeNull();
    });

    test('forbids a head from deleting permanently', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);
        $contract->delete();

        $this->actingAs($head)->delete(route('contracts.force-destroy', $contract))->assertForbidden();

        expect(Contract::withTrashed()->find($contract->id))->not->toBeNull();
    });
});
