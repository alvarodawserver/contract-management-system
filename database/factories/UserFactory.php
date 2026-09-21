<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->withRole(RoleSlug::Admin);
    }

    /**
     * The user heads the given department (or a new one) and is registered as its head.
     */
    public function departmentHead(?Department $department = null): static
    {
        return $this->withRole(RoleSlug::DepartmentHead, $department)
            ->afterCreating(fn (User $user) => $user->employee?->department?->update(['head_id' => $user->employee_id]));
    }

    public function delegatedEmployee(?Department $department = null): static
    {
        return $this->withRole(RoleSlug::DelegatedEmployee, $department);
    }

    /**
     * Gives the user a role and an employee record in the given department (or a new one).
     */
    private function withRole(RoleSlug $slug, ?Department $department = null): static
    {
        return $this->state(fn (): array => [
            'role_id' => Role::firstOrCreate(['slug' => $slug->value], ['name' => $slug->label()])->id,
            'employee_id' => Employee::factory()->for($department ?? Department::factory()),
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     *
     * The users table has no two-factor columns, so this is intentionally a no-op.
     * It only exists for the Fortify two-factor test, which is skipped while the feature is disabled.
     */
    public function withTwoFactor(): static
    {
        return $this;
    }
}
