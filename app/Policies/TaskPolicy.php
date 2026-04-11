<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->role === UserRole::ViceMayor) {
            return $user->managedDepartments()->whereKey($task->department_id)->exists();
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->role === UserRole::ViceMayor) {
            return $user->managedDepartments()->whereKey($task->department_id)->exists();
        }

        return true;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->role === UserRole::ViceMayor) {
            return $user->managedDepartments()->whereKey($task->department_id)->exists();
        }

        return true;
    }

    public function restore(User $user, Task $task): bool
    {
        return true;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return true;
    }
}
