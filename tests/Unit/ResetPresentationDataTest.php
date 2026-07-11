<?php

namespace Tests\Unit;

use App\Models\ClassifierTrainingSample;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Services\ResetPresentationData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetPresentationDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_clears_tasks_but_keeps_departments_and_users(): void
    {
        $dept = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $dept->id]);
        Task::factory()->create(['department_id' => $dept->id, 'assignee_id' => $user->id]);
        ClassifierTrainingSample::query()->create([
            'sikayet_metni' => 'Çöp konteyneri dolu',
            'department_id' => $dept->id,
            'mudurluk_slug' => 'temizlik',
            'is_active' => true,
        ]);

        $stats = (new ResetPresentationData)->reset();

        $this->assertSame(1, $stats['tasks']);
        $this->assertSame(0, Task::query()->count());
        $this->assertSame(1, Department::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, ClassifierTrainingSample::query()->count());
    }
}
