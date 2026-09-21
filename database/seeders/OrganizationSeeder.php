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
     * Fictional organisation: department => head and delegated employees.
     *
     * @var list<array{name: string, code: string, head: array{0: string, 1: string, 2: string}, delegates: list<array{0: string, 1: string, 2: string}>}>
     */
    private const DEPARTMENTS = [
        [
            'name' => 'Urbanismo',
            'code' => 'URB',
            'head' => ['Carmen', 'Ruiz Ortega', 'Jefa de Urbanismo'],
            'delegates' => [
                ['Javier', 'Moreno Pastor', 'Técnico de obras'],
                ['Lucía', 'Navarro Gil', 'Administrativa'],
            ],
        ],
        [
            'name' => 'Cultura',
            'code' => 'CUL',
            'head' => ['Miguel Ángel', 'Serrano Lara', 'Jefe de Cultura'],
            'delegates' => [
                ['Elena', 'Cabrera Rubio', 'Gestora cultural'],
                ['Pablo', 'Iglesias Vega', 'Auxiliar administrativo'],
            ],
        ],
        [
            'name' => 'Medio Ambiente',
            'code' => 'AMB',
            'head' => ['Isabel', 'Domínguez Ríos', 'Jefa de Medio Ambiente'],
            'delegates' => [
                ['Andrés', 'Castillo Marín', 'Técnico ambiental'],
                ['Marta', 'Herrera Soto', 'Administrativa'],
            ],
        ],
        [
            'name' => 'Servicios Sociales',
            'code' => 'SOC',
            'head' => ['Francisco', 'Molina Prieto', 'Jefe de Servicios Sociales'],
            'delegates' => [
                ['Rosa', 'Ortega Blanco', 'Trabajadora social'],
                ['Daniel', 'Gallego Pérez', 'Auxiliar administrativo'],
            ],
        ],
        [
            'name' => 'Hacienda',
            'code' => 'HAC',
            'head' => ['Teresa', 'Vidal Campos', 'Jefa de Hacienda'],
            'delegates' => [
                ['Sergio', 'Romero Díaz', 'Técnico de contratación'],
                ['Nuria', 'Fuentes Cano', 'Administrativa'],
            ],
        ],
    ];

    public function run(): void
    {
        $roleIds = Role::all()->mapWithKeys(fn (Role $role): array => [$role->slug->value => $role->id]);

        foreach (self::DEPARTMENTS as $data) {
            $department = Department::create(['name' => $data['name'], 'code' => $data['code']]);

            $head = $this->createStaff($department, $data['head'], null, $roleIds[RoleSlug::DepartmentHead->value]);
            $department->update(['head_id' => $head->id]);

            foreach ($data['delegates'] as $delegate) {
                $this->createStaff($department, $delegate, $head, $roleIds[RoleSlug::DelegatedEmployee->value]);
            }
        }

        $administration = Department::create(['name' => 'Administración', 'code' => 'ADM']);
        $this->createStaff(
            $administration,
            ['Álvaro', 'Administrador Demo', 'Administrador del sistema'],
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
