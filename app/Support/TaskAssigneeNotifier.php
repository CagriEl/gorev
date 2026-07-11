<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\ExpoPushService;
use Filament\Notifications\Notification;

final class TaskAssigneeNotifier
{
    public static function notifyIfAssigned(Task $task, bool $assigneeChanged, bool $statusChanged): void
    {
        if ($task->assignee_id === null) {
            return;
        }

        if (! in_array($task->status, [TaskStatus::Yonlendirildi, TaskStatus::Sahada], true)) {
            return;
        }

        if (! $assigneeChanged && ! $statusChanged) {
            return;
        }

        $assignee = User::query()->find($task->assignee_id);
        if ($assignee === null) {
            return;
        }

        $title = 'Yeni görev atandı';
        $body = "{$task->task_code} — {$task->title}";

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->icon('heroicon-o-clipboard-document-list')
            ->success();

        $assignee->notifyNow($notification->toDatabase());

        app(ExpoPushService::class)->sendToUser(
            $assignee,
            $title,
            $body,
            [
                'task_id' => $task->id,
                'task_code' => $task->task_code,
            ],
        );
    }
}
