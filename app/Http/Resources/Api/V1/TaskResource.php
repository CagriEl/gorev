<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_code' => $this->task_code,
            'title' => $this->title,
            'department' => [
                'id' => $this->department_id,
                'name' => $this->department?->name,
            ],
            'assignee' => [
                'id' => $this->assignee_id,
                'name' => $this->assignee?->name,
            ],
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'description' => $this->description,
            'solution_note' => $this->solution_note,
            'close_proof_photos' => $this->close_proof_photos,
            'arrival_photos' => $this->arrival_photos,
            'completion_photos' => $this->completion_photos,
            'assigned_at' => optional($this->assigned_at)?->toIso8601String(),
            'dispatched_at' => optional($this->dispatched_at)?->toIso8601String(),
            'resolved_at' => optional($this->resolved_at)?->toIso8601String(),
            'resolve_minutes' => $this->resolve_minutes,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
