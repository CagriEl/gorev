<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Support\ReportScope;
use Illuminate\Auth\Access\AuthorizationException;

final class ReportDashboardService
{
    /**
     * @return array{
     *     scope: string,
     *     summary: array<string, int>,
     *     by_status: array<string, int>,
     *     by_department: list<array{id: int, name: string, open: int, completed: int}>
     * }
     */
    public function build(User $user): array
    {
        if (! in_array($user->role, [UserRole::Manager, UserRole::ViceMayor, UserRole::Admin], true)) {
            throw new AuthorizationException('Rapor yalnızca yönetici rolleri için kullanılabilir.');
        }

        $scope = match ($user->role) {
            UserRole::ViceMayor => 'vice_mayor',
            UserRole::Manager => 'manager',
            default => 'admin',
        };

        $taskQuery = ReportScope::scopedTaskQuery();
        $openQuery = (clone $taskQuery)->whereNotIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi]);

        $summary = [
            'open_tasks' => (int) (clone $openQuery)->count(),
            'critical_open' => (int) (clone $openQuery)->where('priority', TaskPriority::Kritik)->count(),
            'in_field' => (int) (clone $taskQuery)->where('status', TaskStatus::Sahada)->count(),
            'resolved_today' => (int) (clone $taskQuery)
                ->whereIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                ->whereDate('resolved_at', today())
                ->count(),
            'total_staff' => (int) ReportScope::scopedDepartmentQuery()->sum('staff_count'),
        ];

        $byStatus = [
            TaskStatus::Bekliyor->value => (int) (clone $taskQuery)->where('status', TaskStatus::Bekliyor)->count(),
            TaskStatus::Yonlendirildi->value => (int) (clone $taskQuery)->where('status', TaskStatus::Yonlendirildi)->count(),
            TaskStatus::Sahada->value => (int) (clone $taskQuery)->where('status', TaskStatus::Sahada)->count(),
            TaskStatus::OnayBekliyor->value => (int) (clone $taskQuery)->where('status', TaskStatus::OnayBekliyor)->count(),
            TaskStatus::Kapatildi->value => (int) (clone $taskQuery)
                ->whereIn('status', [TaskStatus::Kapatildi, TaskStatus::Tamamlandi])
                ->count(),
        ];

        $byDepartment = ReportScope::scopedDepartmentQuery()
            ->orderBy('name')
            ->get()
            ->map(function (Department $department) use ($taskQuery): array {
                $deptTasks = (clone $taskQuery)->where('department_id', $department->id);

                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'open' => (int) (clone $deptTasks)
                        ->whereNotIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                        ->count(),
                    'completed' => (int) (clone $deptTasks)
                        ->whereIn('status', [TaskStatus::Tamamlandi, TaskStatus::Kapatildi])
                        ->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'scope' => $scope,
            'summary' => $summary,
            'by_status' => $byStatus,
            'by_department' => $byDepartment,
        ];
    }
}
