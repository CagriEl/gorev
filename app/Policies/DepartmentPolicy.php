<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->role === UserRole::ViceMayor) {
            return $department->vice_mayor_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->role === UserRole::ViceMayor) {
            return $department->vice_mayor_id === $user->id;
        }

        return true;
    }

    public function delete(User $user, Department $department): bool
    {
        if ($user->role === UserRole::ViceMayor) {
            return $department->vice_mayor_id === $user->id;
        }

        return true;
    }

    public function restore(User $user, Department $department): bool
    {
        return true;
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return true;
    }
}
