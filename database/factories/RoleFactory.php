<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->attributesFor(RoleSlug::DelegatedEmployee);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => $this->attributesFor(RoleSlug::Admin));
    }

    public function departmentHead(): static
    {
        return $this->state(fn (): array => $this->attributesFor(RoleSlug::DepartmentHead));
    }

    /**
     * @return array{slug: RoleSlug, name: string}
     */
    private function attributesFor(RoleSlug $slug): array
    {
        return ['slug' => $slug, 'name' => $slug->label()];
    }
}
