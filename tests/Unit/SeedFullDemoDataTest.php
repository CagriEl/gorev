<?php

namespace Tests\Unit;

use App\Models\ApprovalRequest;
use App\Models\ClassifierTrainingSample;
use App\Models\PushToken;
use App\Models\Task;
use App\Services\SeedFullDemoData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedFullDemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_comprehensive_demo_data(): void
    {
        $stats = app(SeedFullDemoData::class)->seed(freshImport: true);

        $this->assertSame(21, $stats['import']['departments']);
        $this->assertSame(21, $stats['map_tasks']['total']);
        $this->assertSame(4, $stats['workflow_tasks']);
        $this->assertSame(1, $stats['approvals']['pending']);
        $this->assertGreaterThan(0, $stats['classifier_samples']);
        $this->assertSame(5, $stats['push_tokens']);

        $this->assertGreaterThanOrEqual(25, Task::query()->count());
        $this->assertSame(3, ApprovalRequest::query()->count());
        $this->assertGreaterThan(0, ClassifierTrainingSample::query()->count());
        $this->assertSame(5, PushToken::query()->count());
        $this->assertTrue(\Storage::disk('public')->exists('task-arrival-photos/demo-map.jpg'));
    }
}
