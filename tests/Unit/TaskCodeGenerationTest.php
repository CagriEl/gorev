<?php

namespace Tests\Unit;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ignores_non_numeric_suffix_codes_when_generating_next(): void
    {
        Task::factory()->create(['task_code' => 'KRL-1045']);
        Task::factory()->create(['task_code' => 'KRL-HARITA-99']);
        Task::factory()->create(['task_code' => 'KRL-MAP-01']);

        $this->assertSame('KRL-1046', Task::generateTaskCode());
    }

    public function test_skips_gaps_and_uses_highest_numeric_code(): void
    {
        Task::factory()->create(['task_code' => 'KRL-2000']);
        Task::factory()->create(['task_code' => 'KRL-1045']);

        $this->assertSame('KRL-2001', Task::generateTaskCode());
    }
}
