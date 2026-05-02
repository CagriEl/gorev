<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isViceMayor() || $user->isManager();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isViceMayor()) {
            return $model->department_id !== null
                && in_array((int) $model->department_id, $user->managedDepartmentIds(), true)
                && ! $model->isAdmin();
        }

        if ($user->isManager()) {
            return $user->department_id !== null
                && $model->department_id === $user->department_id
                && $model->isStaff();
        }

        return $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isViceMayor() || $user->isManager();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isViceMayor()) {
            return $model->department_id !== null
                && in_array((int) $model->department_id, $user->managedDepartmentIds(), true)
                && ! $model->isAdmin();
        }

        if ($user->isManager()) {
            return $user->department_id !== null
                && $model->department_id === $user->department_id
                && $model->isStaff();
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
