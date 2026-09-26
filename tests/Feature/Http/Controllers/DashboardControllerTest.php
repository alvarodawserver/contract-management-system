<?php

use App\Models\Contract;
use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->travelTo('2026-09-19 10:00:00'));

test('counts the contracts by derived status', function () {
    $admin = User::factory()->admin()->create();
    Contract::factory()->formalized()->count(2)->create();
    Contract::factory()->count(3)->create();
    Contract::factory()->create(['formalization_deadline' => '2026-09-01'])->delete();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('totals.formalized', 2)
            ->where('totals.pending', 3)
            ->where('totals.lapsed', 1));
});

test('shows a user only the figures of their department', function () {
    $head = User::factory()->departmentHead()->create();
    Contract::factory()->create(['department_id' => $head->departmentId()]);
    Contract::factory()->count(2)->create();

    $this->actingAs($head)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.pending', 1)
            ->has('departments', 1));
});

test('lets an admin filter by department', function () {
    $admin = User::factory()->admin()->create();
    $department = Department::factory()->create();
    Contract::factory()->count(2)->create(['department_id' => $department->id]);
    Contract::factory()->create();

    $this->actingAs($admin)->get(route('dashboard', ['department_id' => $department->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.pending', 2)
            ->where('filters.department_id', $department->id));
});

test('keeps the contracts whose term overlaps the period', function () {
    $admin = User::factory()->admin()->create();
    Contract::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'expected_date' => null]);
    Contract::factory()->create(['start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'expected_date' => null]);

    $this->actingAs($admin)->get(route('dashboard', ['from' => '2026-05-01', 'to' => '2026-08-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.pending', 2)
            ->where('filters.from', '2026-05-01')
            ->where('filters.to', '2026-08-31'));

    $this->get(route('dashboard', ['from' => '2026-09-01']))
        ->assertInertia(fn (Assert $page) => $page->where('totals.pending', 1));
});

test('adds up the final amount when known and the expected one otherwise', function () {
    $admin = User::factory()->admin()->create();
    Contract::factory()->create(['amount' => 1000, 'expected_amount' => 5000]);
    Contract::factory()->create(['amount' => null, 'expected_amount' => 2500]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('totals.amount', fn ($amount) => $amount == 3500));
});

test('lists the pending contracts closest to their deadline, at most five', function () {
    $admin = User::factory()->admin()->create();
    foreach (['2026-10-03', '2026-10-01', '2026-10-06', '2026-10-02', '2026-10-05', '2026-10-04'] as $deadline) {
        Contract::factory()->create(['formalization_deadline' => $deadline]);
    }
    Contract::factory()->formalized()->create(['formalization_deadline' => '2026-09-25']);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcoming', 5)
            ->where('upcoming.0.formalization_deadline', '2026-10-01')
            ->where('upcoming.4.formalization_deadline', '2026-10-05'));
});

test('counts the contracts of each department', function () {
    $admin = User::factory()->admin()->create();
    $first = Department::factory()->create();
    $second = Department::factory()->create();
    Contract::factory()->count(2)->create(['department_id' => $first->id]);
    Contract::factory()->create(['department_id' => $second->id]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('byDepartment', fn ($rows) => collect($rows)->firstWhere('id', $first->id)['contracts'] === 2
            && collect($rows)->firstWhere('id', $second->id)['contracts'] === 1));
});

test('lists every contract within the filtered scope, regardless of status', function () {
    $admin = User::factory()->admin()->create();
    Contract::factory()->formalized()->create();
    Contract::factory()->create();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('contracts.data', 2));
});

test('scopes the contract list to the department and period filters', function () {
    $admin = User::factory()->admin()->create();
    $department = Department::factory()->create();
    Contract::factory()->create(['department_id' => $department->id]);
    Contract::factory()->create();

    $this->actingAs($admin)->get(route('dashboard', ['department_id' => $department->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.data.0.department.id', $department->id));
});

test('tells the client what the user can do with each listed contract', function () {
    $head = User::factory()->departmentHead()->create();
    Contract::factory()->create(['department_id' => $head->departmentId()]);

    $this->actingAs($head)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('contracts.data.0.can.update', true));
});

test('rejects a period that ends before it starts', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('dashboard', ['from' => '2026-09-01', 'to' => '2026-08-01']))
        ->assertSessionHasErrors('to');
});

test('forbids a delegated employee, who has "My contracts" instead', function () {
    $employee = User::factory()->delegatedEmployee()->create();

    $this->actingAs($employee)->get(route('dashboard'))->assertForbidden();
});

test('forbids a user without a role', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
});
