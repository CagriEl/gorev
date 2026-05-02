<?php

namespace App\Support;

use App\Models\Department;
use Illuminate\Validation\ValidationException;

final class TaskForemanAssignee
{
    /**
     * Panel: müdürlükteki saha şefi kullanıcısını görev atanan olarak ayarlar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function syncFromDepartment(array $data): array
    {
        $deptId = $data['department_id'] ?? null;
        if (! $deptId) {
            $data['assignee_id'] = null;

            return $data;
        }
        $dept = Department::query()->find($deptId);
        $data['assignee_id'] = $dept?->foreman_user_id;

        return $data;
    }

    /**
     * API: müdürlük saha şefi kuralına göre assignee_id doğrular ve ayarlar.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function applyForDepartment(array $validated, Department $department, mixed $incomingAssignee): array
    {
        if ($department->foreman_user_id !== null) {
            if ($incomingAssignee !== null && $incomingAssignee !== ''
                && (int) $incomingAssignee !== (int) $department->foreman_user_id) {
                throw ValidationException::withMessages([
                    'assignee_id' => 'Atanan yalnızca bu müdürlüğün saha şefi kullanıcısı olabilir.',
                ]);
            }
            $validated['assignee_id'] = (int) $department->foreman_user_id;

            return $validated;
        }

        if ($incomingAssignee !== null && $incomingAssignee !== '') {
            throw ValidationException::withMessages([
                'assignee_id' => 'Bu müdürlükte saha şefi kullanıcısı tanımlı değil; atanan kişi gönderilemez.',
            ]);
        }
        $validated['assignee_id'] = null;

        return $validated;
    }
}
