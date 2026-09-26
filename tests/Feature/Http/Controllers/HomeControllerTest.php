<?php

use App\Models\User;

test('sends an admin to the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

test('sends a department head to the dashboard', function () {
    $this->actingAs(User::factory()->departmentHead()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

test('sends a delegated employee to their contracts', function () {
    $this->actingAs(User::factory()->delegatedEmployee()->create())
        ->get(route('home'))
        ->assertRedirect(route('contracts.index'));
});

test('sends a user without a role to their contracts', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('contracts.index'));
});
