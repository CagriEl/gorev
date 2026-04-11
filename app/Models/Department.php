<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'vice_mayor_id',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
