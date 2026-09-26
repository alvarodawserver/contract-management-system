<?php

use App\Enums\MovementAction;
use App\Models\Contract;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    test('forbids a delegated employee, who has each contract\'s own history instead', function () {
        $this->actingAs(User::factory()->delegatedEmployee()->create())
            ->get(route('movements.index'))
            ->assertForbidden();
    });

    test('shows a head only the movements of contracts in their department', function () {
        $head = User::factory()->departmentHead()->create();
        Contract::factory()->create(['department_id' => $head->departmentId()]);
        Contract::factory()->create();

        $this->actingAs($head)->get(route('movements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('movements/index')
                ->has('movements.data', 1));
    });

    test('shows an admin every movement, including one whose contract was permanently deleted', function () {
        $admin = User::factory()->admin()->create();
        Contract::factory()->count(2)->create();
        Contract::factory()->create()->delete();
        $orphaned = Contract::onlyTrashed()->first();
        $orphaned->forceDelete();

        $this->actingAs($admin)->get(route('movements.index'))
            ->assertInertia(fn (Assert $page) => $page
                // created + created + (created, deleted, force_deleted) = 5
                ->has('movements.data', 5));
    });

    test('orders movements newest first', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $first = Contract::factory()->create();
        $first->update(['title' => 'Updated']);

        $this->get(route('movements.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('movements.data.0.action', MovementAction::Updated->value)
                ->where('movements.data.1.action', MovementAction::Created->value));
    });
});

describe('forContract', function () {
    test('shows the movements of that contract, newest first, with who made them', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);
        $this->actingAs($head)->put(route('contracts.update', $contract), ['title' => 'Nuevo título']);

        $this->get(route('contracts.movements.index', $contract))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contracts/movements')
                ->where('contract.id', $contract->id)
                ->has('movements.data', 2)
                ->where('movements.data.0.action', MovementAction::Updated->value)
                ->where('movements.data.0.user.id', $head->id)
                ->where('movements.data.1.action', MovementAction::Created->value));
    });

    test('lets a delegated employee view the movements of a contract they did not create', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId()]);

        $this->actingAs($employee)->get(route('contracts.movements.index', $contract))
            ->assertOk();
    });

    test('forbids a contract of another department', function () {
        $employee = User::factory()->delegatedEmployee()->create();

        $this->actingAs($employee)
            ->get(route('contracts.movements.index', Contract::factory()->create()))
            ->assertForbidden();
    });

    test('does not include movements of other contracts', function () {
        $admin = User::factory()->admin()->create();
        $contract = Contract::factory()->create();
        Contract::factory()->create();

        $this->actingAs($admin)->get(route('contracts.movements.index', $contract))
            ->assertInertia(fn (Assert $page) => $page->has('movements.data', 1));
    });
});
