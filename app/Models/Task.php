<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Support\SlaCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Task extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

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
        'solution_note',
        'close_proof_photos',
        'arrival_photos',
        'completion_photos',
        'assigned_at',
        'dispatched_at',
        'resolved_at',
        'resolve_minutes',
        'field_completed_at',
        'field_completion_distance_m',
        'field_completion_latitude',
        'field_completion_longitude',
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
            'field_completed_at' => 'datetime',
            'field_completion_distance_m' => 'decimal:2',
            'field_completion_latitude' => 'decimal:7',
            'field_completion_longitude' => 'decimal:7',
            'close_proof_photos' => 'array',
            'arrival_photos' => 'array',
            'completion_photos' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'task_code',
                'title',
                'department_id',
                'assignee_id',
                'priority',
                'status',
                'description',
                'solution_note',
                'assigned_at',
                'dispatched_at',
                'resolved_at',
                'resolve_minutes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('task');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge([
            'event' => $eventName,
            'request_context' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'route' => request()->path(),
            ],
        ]);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public static function generateTaskCode(): string
    {
        $max = 1044;

        static::withTrashed()
            ->where('task_code', 'like', 'KRL-%')
            ->pluck('task_code')
            ->each(function (string $code) use (&$max): void {
                if (preg_match('/^KRL-(\d+)$/', $code, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            });

        return 'KRL-'.($max + 1);
    }

    public function resolveDurationMinutes(): ?int
    {
        if ($this->resolve_minutes !== null) {
            return $this->resolve_minutes;
        }

        if (! in_array($this->status, [TaskStatus::Tamamlandi, TaskStatus::Cozuldu, TaskStatus::Kapatildi], true)
            || $this->resolved_at === null
            || $this->assigned_at === null) {
            return null;
        }

        return max(0, SlaCalculator::calendarMinutes($this->assigned_at, $this->resolved_at));
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

    public function isClosed(): bool
    {
        return in_array($this->status, [TaskStatus::Kapatildi, TaskStatus::Tamamlandi], true);
    }
}
