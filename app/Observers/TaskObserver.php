<?php

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\TaskAssigneeNotifier;

class TaskObserver
{
    public function created(Task $task): void
    {
        TaskAssigneeNotifier::notifyIfAssigned($task, assigneeChanged: true, statusChanged: true);
    }

    public function updated(Task $task): void
    {
        $assigneeChanged = $task->wasChanged('assignee_id');
        $statusChanged = $task->wasChanged('status')
            && $task->status === TaskStatus::Yonlendirildi;

        TaskAssigneeNotifier::notifyIfAssigned($task, $assigneeChanged, $statusChanged);
    }
}
