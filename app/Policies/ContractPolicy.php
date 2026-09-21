<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin) || $user->departmentId() !== null;
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->hasRole(RoleSlug::Admin) || $this->belongsToUserDepartment($user, $contract);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Admins and department heads manage every contract they can see; a delegated employee only their own.
     */
    public function update(User $user, Contract $contract): bool
    {
        if ($user->hasRole(RoleSlug::Admin)) {
            return true;
        }

        if (! $this->belongsToUserDepartment($user, $contract)) {
            return false;
        }

        return $user->hasRole(RoleSlug::DepartmentHead)
            || ($user->hasRole(RoleSlug::DelegatedEmployee) && $contract->created_by === $user->id);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function viewTrashed(User $user): bool
    {
        return $user->hasRole(RoleSlug::Admin) || $user->hasRole(RoleSlug::DepartmentHead);
    }

    public function restore(User $user, Contract $contract): bool
    {
        return $user->hasRole(RoleSlug::Admin)
            || ($user->hasRole(RoleSlug::DepartmentHead) && $this->belongsToUserDepartment($user, $contract));
    }

    public function forceDelete(User $user, Contract $contract): bool
    {
        return $user->hasRole(RoleSlug::Admin);
    }

    private function belongsToUserDepartment(User $user, Contract $contract): bool
    {
        return $user->departmentId() !== null && $user->departmentId() === $contract->department_id;
    }
}
