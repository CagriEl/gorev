<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApprovalRequestController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorizeReview();

        return ApprovalRequestResource::collection(
            ApprovalRequest::query()
                ->with(['requester:id,name', 'approver:id,name'])
                ->latest('id')
                ->paginate(25),
        );
    }

    public function show(ApprovalRequest $approvalRequest): ApprovalRequestResource
    {
        $this->authorizeReview();

        return new ApprovalRequestResource($approvalRequest->load(['requester:id,name', 'approver:id,name']));
    }

    public function approve(ApprovalRequest $approvalRequest): ApprovalRequestResource
    {
        $this->authorizeReview();
        abort_if($approvalRequest->status !== ApprovalRequest::STATUS_PENDING, 422, 'Talep artık beklemede değil.');
        $this->applyApproval($approvalRequest);

        return new ApprovalRequestResource($approvalRequest->refresh()->load(['requester:id,name', 'approver:id,name']));
    }

    public function reject(ApprovalRequest $approvalRequest): ApprovalRequestResource
    {
        $this->authorizeReview();
        abort_if($approvalRequest->status !== ApprovalRequest::STATUS_PENDING, 422, 'Talep artık beklemede değil.');
        $approvalRequest->update([
            'status' => ApprovalRequest::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return new ApprovalRequestResource($approvalRequest->refresh()->load(['requester:id,name', 'approver:id,name']));
    }

    protected function authorizeReview(): void
    {
        $user = auth()->user();
        abort_unless($user?->isAdmin() || $user?->isViceMayor(), 403);
    }

    protected function applyApproval(ApprovalRequest $record): void
    {
        if ($record->type === 'user_role_change') {
            $target = User::query()->find($record->approvable_id);
            if ($target) {
                $target->update(['role' => $record->payload['to_role'] ?? $target->role->value]);
            }
        } elseif ($record->type === 'department_vice_mayor_change') {
            $target = Department::query()->find($record->approvable_id);
            if ($target) {
                $target->update(['vice_mayor_id' => $record->payload['to_vice_mayor_id'] ?? $target->vice_mayor_id]);
            }
        } elseif ($record->type === 'task_close') {
            $target = Task::query()->find($record->approvable_id);
            if ($target && $target->status === TaskStatus::OnayBekliyor) {
                $target->update(['status' => TaskStatus::Kapatildi]);
            }
        }

        $record->update([
            'status' => ApprovalRequest::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }
}
