<?php

namespace Database\Seeders;

use App\Models\ApprovalRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalRequestSeeder extends Seeder
{
    public function run(): void
    {
        $requester = User::query()->where('email', 'saha@kirklareli.bel.tr')->first()
            ?? User::query()->first();
        $task = Task::query()->latest('id')->first();

        if (! $requester || ! $task) {
            return;
        }

        ApprovalRequest::query()->updateOrCreate(
            [
                'type' => 'task_close',
                'approvable_type' => $task->getMorphClass(),
                'approvable_id' => $task->id,
                'requested_by' => $requester->id,
            ],
            [
                'status' => ApprovalRequest::STATUS_PENDING,
                'reason' => 'Deneme onay talebi (seed).',
                'payload' => [
                    'sample' => true,
                    'source' => 'ApprovalRequestSeeder',
                ],
            ],
        );
    }
}
