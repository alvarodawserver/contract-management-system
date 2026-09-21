<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    /**
     * Fictional organisation: three departments, each with a head and one delegated employee.
     *
     * @var list<array{name: string, code: string, head: array{0: string, 1: string, 2: string}, employee: array{0: string, 1: string, 2: string}}>
     */
    private const DEPARTMENTS = [
        [
            'name' => 'Urbanismo',
            'code' => 'URB',
            'head' => ['Carmen', 'Ruiz Ortega', 'Jefa de Urbanismo'],
            'employee' => ['Javier', 'Moreno Pastor', 'Técnico de obras'],
        ],
        [
            'name' => 'Cultura',
            'code' => 'CUL',
            'head' => ['Miguel Ángel', 'Serrano Lara', 'Jefe de Cultura'],
            'employee' => ['Elena', 'Cabrera Rubio', 'Gestora cultural'],
        ],
        [
            'name' => 'Medio Ambiente',
            'code' => 'AMB',
            'head' => ['Isabel', 'Domínguez Ríos', 'Jefa de Medio Ambiente'],
            'employee' => ['Andrés', 'Castillo Marín', 'Técnico ambiental'],
        ],
    ];

    public function run(): void
    {
        $roleIds = Role::all()->mapWithKeys(fn (Role $role): array => [$role->slug->value => $role->id]);

        foreach (self::DEPARTMENTS as $data) {
            $department = Department::create(['name' => $data['name'], 'code' => $data['code']]);

            $head = $this->createStaff($department, $data['head'], null, $roleIds[RoleSlug::DepartmentHead->value]);
            $department->update(['head_id' => $head->id]);

            $this->createStaff($department, $data['employee'], $head, $roleIds[RoleSlug::DelegatedEmployee->value]);
        }

        $this->createStaff(
            Department::create(['name' => 'Administración', 'code' => 'ADM']),
            ['Admin', 'Demo', 'Administrador del sistema'],
            null,
            $roleIds[RoleSlug::Admin->value],
        );
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $person  first name, last name, position
     */
    private function createStaff(Department $department, array $person, ?Employee $supervisor, int $roleId): Employee
    {
        [$firstName, $lastName, $position] = $person;

        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'supervisor_id' => $supervisor?->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'position' => $position,
        ]);

        User::factory()->create([
            'employee_id' => $employee->id,
            'role_id' => $roleId,
            'name' => $employee->full_name,
            'email' => Str::slug($firstName.' '.Str::before($lastName, ' '), '.').'@ayuntamiento.test',
        ]);

        return $employee;
    }
}
