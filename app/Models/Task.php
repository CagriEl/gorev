<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Task extends Model
{
    use LogsActivity;

    public const SLA_TARGET_MINUTES = 120;

    protected $fillable = [
        'task_code',
        'title',
        'department_id',
        'assignee_id',
        'location',
        'latitude',
        'longitude',
        'priority',
        'status',
        'description',
        'assigned_at',
        'dispatched_at',
        'resolved_at',
        'resolve_minutes',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'assigned_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'resolved_at' => 'datetime',
            'resolve_minutes' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('task');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public static function generateTaskCode(): string
    {
        $last = static::query()->orderByDesc('id')->value('task_code');
        $next = 1045;
        if ($last && preg_match('/KRL-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return 'KRL-'.$next;
    }

    public function resolveDurationMinutes(): ?int
    {
        if ($this->resolve_minutes !== null) {
            return $this->resolve_minutes;
        }

        if ($this->status !== TaskStatus::Tamamlandi || $this->resolved_at === null || $this->assigned_at === null) {
            return null;
        }

        return max(0, (int) round($this->assigned_at->diffInMinutes($this->resolved_at)));
    }

    public function slaWithinTarget(?int $targetMinutes = null): ?bool
    {
        $targetMinutes ??= self::SLA_TARGET_MINUTES;
        $minutes = $this->resolveDurationMinutes();
        if ($minutes === null) {
            return null;
        }

        return $minutes < $targetMinutes;
    }
}
