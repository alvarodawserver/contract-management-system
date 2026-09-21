<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleSlug::cases() as $slug) {
            Role::firstOrCreate(['slug' => $slug->value], ['name' => $slug->label()]);
        }
    }
}
