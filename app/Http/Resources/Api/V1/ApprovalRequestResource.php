<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'payload' => $this->payload,
            'requested_by' => [
                'id' => $this->requested_by,
                'name' => $this->requester?->name,
            ],
            'approved_by' => [
                'id' => $this->approved_by,
                'name' => $this->approver?->name,
            ],
            'approvable' => [
                'type' => $this->approvable_type,
                'id' => $this->approvable_id,
            ],
            'reviewed_at' => optional($this->reviewed_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
