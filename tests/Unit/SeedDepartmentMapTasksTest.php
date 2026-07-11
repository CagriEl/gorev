<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Task;
use App\Services\SeedDepartmentMapTasks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDepartmentMapTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_map_task_per_department_with_coordinates(): void
    {
        $deptA = Department::factory()->create(['name' => 'Fen İşleri Müdürlüğü']);
        $deptB = Department::factory()->create(['name' => 'Temizlik İşleri Müdürlüğü']);

        $stats = (new SeedDepartmentMapTasks)->seed();

        $this->assertSame(2, $stats['total']);
        $this->assertSame(2, $stats['created']);

        foreach ([$deptA, $deptB] as $dept) {
            $task = Task::query()->where('task_code', 'KRL-HARITA-'.$dept->id)->first();
            $this->assertNotNull($task);
            $this->assertSame($dept->id, $task->department_id);
            $this->assertNotNull($task->latitude);
            $this->assertNotNull($task->longitude);
            $this->assertFalse($task->isClosed());
        }
    }
}
