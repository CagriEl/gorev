<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'vice_mayor_id',
        'foreman_user_id',
        'manager_name',
        'manager_phone',
        'foreman_name',
        'foreman_phone',
        'staff_count',
    ];

    protected function casts(): array
    {
        return [
            'staff_count' => 'integer',
        ];
    }

    public function viceMayor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vice_mayor_id');
    }

    public function foremanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'foreman_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'vice_mayor_id',
                'foreman_user_id',
                'manager_name',
                'manager_phone',
                'foreman_name',
                'foreman_phone',
                'staff_count',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('department');
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
}
