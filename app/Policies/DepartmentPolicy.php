<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isViceMayor() || $user->isManager();
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->isViceMayor()) {
            return $department->vice_mayor_id === $user->id;
        }

        if ($user->isManager()) {
            return $user->department_id !== null && $user->department_id === $department->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Department $department): bool
    {
        return $user->isViceMayor() && $department->vice_mayor_id === $user->id;
    }

    public function delete(User $user, Department $department): bool
    {
        return false;
    }

    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
