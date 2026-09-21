<?php

use App\Http\Middleware\SimulateUser;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('acts as the admin when no user has been chosen', function () {
    $admin = User::factory()->admin()->create();

    $this->get(route('contracts.index'))
        ->assertInertia(fn (Assert $page) => $page->where('simulation.current', $admin->id));
});

test('remembers the user chosen in the selector', function () {
    $employee = User::factory()->delegatedEmployee()->create();

    $this->post(route('simulated-user.store'), ['user_id' => $employee->id])
        ->assertRedirect()
        ->assertSessionHas(SimulateUser::SESSION_KEY, $employee->id);
});

test('acts as the user remembered in the session', function () {
    User::factory()->admin()->create();
    $employee = User::factory()->delegatedEmployee()->create();

    $this->withSession([SimulateUser::SESSION_KEY => $employee->id])
        ->get(route('contracts.index'))
        ->assertInertia(fn (Assert $page) => $page->where('simulation.current', $employee->id));
});

test('rejects a user that does not exist', function () {
    $this->post(route('simulated-user.store'), ['user_id' => 999])
        ->assertSessionHasErrors('user_id');
});

test('lists the selector users with role and department, admin first then heads then employees', function () {
    $employee = User::factory()->delegatedEmployee()->create();
    $admin = User::factory()->admin()->create();
    $head = User::factory()->departmentHead()->create();

    $this->actingAs($admin)->get(route('contracts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('simulation.users', 3)
            ->where('simulation.users.0.id', $admin->id)
            ->where('simulation.users.0.role', 'admin')
            ->where('simulation.users.1.id', $head->id)
            ->where('simulation.users.2.id', $employee->id)
            ->where('simulation.users.2.department', $employee->employee->department->name));
});

test('does not replace a user who is really logged in', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->delegatedEmployee()->create();

    $this->withSession([SimulateUser::SESSION_KEY => $admin->id])
        ->actingAs($employee)
        ->get(route('contracts.index'))
        ->assertInertia(fn (Assert $page) => $page->where('simulation.current', $employee->id));
});

test('nobody is simulated when there is no admin and no user was chosen', function () {
    $this->get(route('contracts.index'))->assertRedirect(route('login'));
});
