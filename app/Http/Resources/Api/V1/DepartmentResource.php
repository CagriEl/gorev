<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vice_mayor' => [
                'id' => $this->vice_mayor_id,
                'name' => $this->viceMayor?->name,
            ],
            'manager_name' => $this->manager_name,
            'manager_phone' => $this->manager_phone,
            'foreman_name' => $this->foreman_name,
            'foreman_phone' => $this->foreman_phone,
            'foreman_user_id' => $this->foreman_user_id,
            'foreman_user' => [
                'id' => $this->foreman_user_id,
                'name' => $this->foremanUser?->name,
            ],
            'staff_count' => $this->staff_count,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
