<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isViceMayor()) {
            return in_array($task->department_id, $user->managedDepartmentIds(), true);
        }

        if ($user->isManager()) {
            return $user->department_id !== null && $user->department_id === $task->department_id;
        }

        if ($user->isStaff()) {
            return $task->assignee_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isViceMayor() || $user->isManager();
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isViceMayor()) {
            return in_array($task->department_id, $user->managedDepartmentIds(), true);
        }

        if ($user->isManager()) {
            return $user->department_id !== null && $user->department_id === $task->department_id;
        }

        if ($user->isStaff()) {
            return $task->assignee_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        return false;
    }

    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
