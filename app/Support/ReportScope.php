<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

final class ReportScope
{
    /**
     * Dashboard / widget sorguları: başkan yardımcısı yalnızca bağlı müdürlüklerin görevlerini görür.
     */
    public static function scopedTaskQuery(): Builder
    {
        $query = Task::query();

        if (auth()->check() && auth()->user()->role === UserRole::ViceMayor) {
            $managedDeptIds = auth()->user()->managedDepartments->pluck('id');
            $query->whereIn('department_id', $managedDeptIds);
        }

        return $query;
    }

    /**
     * Dashboard / widget sorguları: başkan yardımcısı yalnızca kendi müdürlüklerini görür.
     */
    public static function scopedDepartmentQuery(): Builder
    {
        $query = Department::query();

        if (auth()->check() && auth()->user()->role === UserRole::ViceMayor) {
            $managedDeptIds = auth()->user()->managedDepartments->pluck('id');
            $query->whereIn('id', $managedDeptIds);
        }

        return $query;
    }

}
