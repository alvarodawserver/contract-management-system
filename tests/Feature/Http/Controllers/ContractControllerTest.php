<?php

use App\Enums\MovementAction;
use App\Models\Contract;
use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    test('lists only the contracts of the user department', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        Contract::factory()->count(2)->create(['department_id' => $employee->departmentId()]);
        Contract::factory()->create();

        $this->actingAs($employee)->get(route('contracts.index'))
            ->assertInertia(fn (Assert $page) => $page->component('contracts/index')->has('contracts.data', 2));
    });

    test('lists every department to an admin', function () {
        $admin = User::factory()->admin()->create();
        Contract::factory()->count(2)->create();

        $this->actingAs($admin)->get(route('contracts.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('contracts.data', 2)
                ->has('departments', Department::count()));
    });

    test('lets an admin filter by department', function () {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();
        Contract::factory()->create(['department_id' => $department->id]);
        Contract::factory()->create();

        $this->actingAs($admin)->get(route('contracts.index', ['department_id' => $department->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('contracts.data', 1)
                ->where('filters.department_id', $department->id));
    });

    test('filters by derived status', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        Contract::factory()->formalized()->create(['department_id' => $employee->departmentId()]);
        Contract::factory()->create(['department_id' => $employee->departmentId()]);

        $this->actingAs($employee)->get(route('contracts.index', ['status' => 'formalized']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('contracts.data', 1)
                ->where('contracts.data.0.status', 'formalized'));
    });

    test('searches by title or reference and not by responsible', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $department = $employee->departmentId();
        Contract::factory()->create(['department_id' => $department, 'title' => 'Renovación del alumbrado']);
        Contract::factory()->create(['department_id' => $department, 'title' => 'Limpieza', 'responsible' => 'Alumbrados S.A.']);

        $this->actingAs($employee)->get(route('contracts.index', ['search' => 'ALUMBRADO']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('contracts.data', 1)
                ->where('contracts.data.0.title', 'Renovación del alumbrado'));
    });

    test('does not list deleted contracts', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        Contract::factory()->create(['department_id' => $employee->departmentId()])->delete();

        $this->actingAs($employee)->get(route('contracts.index'))
            ->assertInertia(fn (Assert $page) => $page->has('contracts.data', 0));
    });

    test('rejects lapsed as a status filter because those contracts are in the trash', function () {
        $employee = User::factory()->delegatedEmployee()->create();

        $this->actingAs($employee)->get(route('contracts.index', ['status' => 'lapsed']))
            ->assertSessionHasErrors('status');
    });

    test('tells the client what the user can do with each contract', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $department = $employee->departmentId();
        Contract::factory()->create(['department_id' => $department, 'created_by' => $employee->id]);
        Contract::factory()->create(['department_id' => $department]);

        $this->actingAs($employee)->get(route('contracts.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('contracts.data.0.can.update', false)
                ->where('contracts.data.1.can.update', true));
    });

    test('forbids a user without a department', function () {
        $this->actingAs(User::factory()->create())->get(route('contracts.index'))->assertForbidden();
    });
});

describe('store', function () {
    test('creates a contract in the department of the user and records the creator', function () {
        $employee = User::factory()->delegatedEmployee()->create();

        $response = $this->actingAs($employee)
            ->post(route('contracts.store'), ['title' => 'Obras de reforma', 'expected_amount' => 15000]);

        $contract = Contract::sole();
        $response->assertRedirect(route('contracts.show', $contract));
        expect($contract->department_id)->toBe($employee->departmentId());
        expect($contract->created_by)->toBe($employee->id);
        expect($contract->expected_amount)->toBe('15000.00');
        expect($contract->reference)->toStartWith('CT-');
        expect($contract->formalization_deadline)->not->toBeNull();
    });

    test('ignores the department sent by a user who is not an admin', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $other = Department::factory()->create();

        $this->actingAs($employee)
            ->post(route('contracts.store'), ['title' => 'Obras', 'department_id' => $other->id]);

        expect(Contract::sole()->department_id)->toBe($employee->departmentId());
    });

    test('lets an admin choose the department', function () {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();

        $this->actingAs($admin)
            ->post(route('contracts.store'), ['title' => 'Obras', 'department_id' => $department->id]);

        expect(Contract::sole()->department_id)->toBe($department->id);
    });

    test('requires an admin to choose the department', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('contracts.store'), ['title' => 'Obras'])
            ->assertSessionHasErrors('department_id');

        expect(Contract::count())->toBe(0);
    });

    test('accepts an end date while there is no start date yet', function () {
        $employee = User::factory()->delegatedEmployee()->create();

        $this->actingAs($employee)
            ->post(route('contracts.store'), ['title' => 'Obras', 'end_date' => '2027-01-31'])
            ->assertSessionHasNoErrors();

        expect(Contract::count())->toBe(1);
    });

    test('rejects invalid contract data', function (array $payload, string $field) {
        $employee = User::factory()->delegatedEmployee()->create();

        $this->actingAs($employee)->post(route('contracts.store'), $payload)
            ->assertSessionHasErrors($field);

        expect(Contract::count())->toBe(0);
    })->with([
        'missing title' => [[], 'title'],
        'end before start' => [['title' => 'Obras', 'start_date' => '2026-10-01', 'end_date' => '2026-09-01'], 'end_date'],
        'negative amount' => [['title' => 'Obras', 'amount' => -5], 'amount'],
        'amount too large' => [['title' => 'Obras', 'expected_amount' => 100000000000], 'expected_amount'],
        'not a date' => [['title' => 'Obras', 'start_date' => 'not-a-date'], 'start_date'],
    ]);

    test('forbids a user without a department', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('contracts.store'), ['title' => 'Obras'])
            ->assertForbidden();
    });
});

describe('show', function () {
    test('shows the contract data and what the current user can do with it', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $this->actingAs($employee);
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId(), 'created_by' => $employee->id]);

        $this->get(route('contracts.show', $contract))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contracts/show')
                ->where('contract.id', $contract->id)
                ->where('contract.reference', $contract->reference)
                ->where('contract.can.update', true)
                ->where('contract.can.delete', true)
                ->missing('contract.movements'));
    });

    test('forbids a contract of another department', function () {
        $employee = User::factory()->delegatedEmployee()->create();

        $this->actingAs($employee)->get(route('contracts.show', Contract::factory()->create()))
            ->assertForbidden();
    });

    test('returns 404 for a deleted contract', function () {
        $admin = User::factory()->admin()->create();
        $contract = Contract::factory()->create();
        $contract->delete();

        $this->actingAs($admin)->get(route('contracts.show', $contract))->assertNotFound();
    });
});

describe('update', function () {
    test('lets a delegated employee open the form of a contract they created', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId(), 'created_by' => $employee->id]);

        $this->actingAs($employee)->get(route('contracts.edit', $contract))
            ->assertInertia(fn (Assert $page) => $page->component('contracts/edit')->where('contract.id', $contract->id));
    });

    test('forbids a delegated employee from editing a contract created by someone else', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId(), 'title' => 'Original']);

        $this->actingAs($employee)->get(route('contracts.edit', $contract))->assertForbidden();
        $this->put(route('contracts.update', $contract), ['title' => 'Cambiado'])->assertForbidden();

        expect($contract->fresh()->title)->toBe('Original');
    });

    test('lets a head update any contract of the department and records the change', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);

        $this->actingAs($head)
            ->put(route('contracts.update', $contract), ['title' => 'Nuevo título', 'amount' => 9000])
            ->assertRedirect(route('contracts.show', $contract));

        $movement = $contract->movements()->where('action', MovementAction::Updated)->sole();
        expect($contract->fresh()->title)->toBe('Nuevo título');
        expect($movement->user_id)->toBe($head->id);
        expect($movement->changes['amount']['new'])->toBe('9000.00');
    });

    test('only touches the fields sent, leaving the rest exactly as they were', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create([
            'department_id' => $head->departmentId(),
            'type' => 'Servicios',
            'description' => 'Descripción original',
            'expected_amount' => 5000,
        ]);

        // The future "formalize" dialog sends only these four fields.
        $this->actingAs($head)->put(route('contracts.update', $contract), [
            'amount' => 12000,
            'start_date' => '2026-10-01',
            'end_date' => '2027-09-30',
            'responsible' => 'Acme S.L.',
        ]);

        $fresh = $contract->fresh();
        expect($fresh->amount)->toBe('12000.00');
        expect($fresh->responsible)->toBe('Acme S.L.');
        expect($fresh->title)->toBe($contract->title);
        expect($fresh->type)->toBe('Servicios');
        expect($fresh->description)->toBe('Descripción original');
        expect($fresh->expected_amount)->toBe('5000.00');
    });

    test('forbids the head of another department', function () {
        $head = User::factory()->departmentHead()->create();

        $this->actingAs($head)
            ->put(route('contracts.update', Contract::factory()->create()), ['title' => 'Cambiado'])
            ->assertForbidden();
    });

    test('does not move a contract to another department', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);
        $other = Department::factory()->create();

        $this->actingAs($head)
            ->put(route('contracts.update', $contract), ['title' => 'Obras', 'department_id' => $other->id]);

        expect($contract->fresh()->department_id)->toBe($head->departmentId());
    });

    test('rejects invalid data', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);

        $this->actingAs($head)
            ->put(route('contracts.update', $contract), ['title' => 'Obras', 'start_date' => '2026-10-01', 'end_date' => '2026-09-01'])
            ->assertSessionHasErrors('end_date');
    });
});

describe('destroy', function () {
    test('soft deletes a contract the delegated employee created and records who did it', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId(), 'created_by' => $employee->id]);

        $this->actingAs($employee)->delete(route('contracts.destroy', $contract))
            ->assertRedirect(route('contracts.index'));

        expect(Contract::find($contract->id))->toBeNull();
        expect($contract->movements()->where('action', MovementAction::Deleted)->sole()->user_id)->toBe($employee->id);
    });

    test('forbids a delegated employee from deleting a contract created by someone else', function () {
        $employee = User::factory()->delegatedEmployee()->create();
        $contract = Contract::factory()->create(['department_id' => $employee->departmentId()]);

        $this->actingAs($employee)->delete(route('contracts.destroy', $contract))->assertForbidden();

        expect($contract->fresh()->trashed())->toBeFalse();
    });

    test('lets a head delete any contract of the department', function () {
        $head = User::factory()->departmentHead()->create();
        $contract = Contract::factory()->create(['department_id' => $head->departmentId()]);

        $this->actingAs($head)->delete(route('contracts.destroy', $contract));

        expect(Contract::find($contract->id))->toBeNull();
    });
});
