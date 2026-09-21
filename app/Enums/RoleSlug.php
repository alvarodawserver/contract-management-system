<?php

namespace App\Enums;

enum RoleSlug: string
{
    case Admin = 'admin';
    case DepartmentHead = 'department_head';
    case DelegatedEmployee = 'delegated_employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::DepartmentHead => 'Jefe de departamento',
            self::DelegatedEmployee => 'Empleado delegado',
        };
    }
}
