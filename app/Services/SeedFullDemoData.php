<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\ClassifierTrainingSample;
use App\Models\Department;
use App\Models\PushToken;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SeedFullDemoData
{
    private const CENTER_LAT = 41.7333;

    private const CENTER_LNG = 27.2253;

    public function __construct(
        private ImportMunicipalityUsersFromCsv $importer,
        private SeedDepartmentMapTasks $mapTasks,
    ) {}

    /**
     * @return array{
     *     import: array<string, int>|null,
     *     photos: list<string>,
     *     map_tasks: array{created: int, updated: int, skipped: int, total: int},
     *     workflow_tasks: int,
     *     approvals: array{pending: int, approved: int, rejected: int},
     *     classifier_samples: int,
     *     push_tokens: int
     * }
     */
    public function seed(bool $freshImport = true): array
    {
        return DB::transaction(function () use ($freshImport): array {
            $import = null;
            if ($freshImport) {
                $csv = base_path('database/seeders/data/kullanicilar-2026-05-31.csv');
                $import = $this->importer->import($csv, 'admin@admin.com');
            }

            $photos = $this->ensureDemoPhotos();
            $mapTasks = $this->mapTasks->seed();
            $workflowTasks = $this->seedWorkflowTasks();
            $approvals = $this->seedApprovalRequests();
            $classifierSamples = $this->importClassifierSamples();
            $pushTokens = $this->seedPushTokens();

            return [
                'import' => $import,
                'photos' => $photos,
                'map_tasks' => $mapTasks,
                'workflow_tasks' => $workflowTasks,
                'approvals' => $approvals,
                'classifier_samples' => $classifierSamples,
                'push_tokens' => $pushTokens,
            ];
        });
    }

    /**
     * @return list<string>
     */
    private function ensureDemoPhotos(): array
    {
        $source = $this->resolveDemoPhotoSource();
        $disk = Storage::disk('public');
        $written = [];

        foreach ([
            'task-arrival-photos/demo-map.jpg',
            'task-completion-photos/demo-completion.jpg',
            'close-proof-photos/demo-close.jpg',
        ] as $path) {
            $directory = Str::beforeLast($path, '/');
            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }

            $disk->put($path, File::get($source));
            $written[] = $path;
        }

        return $written;
    }

    private function resolveDemoPhotoSource(): string
    {
        foreach ([
            base_path('e2e/fixtures/photo.png'),
            base_path('mobile/assets/icon.png'),
        ] as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Demo fotoğraf kaynağı bulunamadı (e2e/fixtures/photo.png).');
    }

    private function seedWorkflowTasks(): int
    {
        $departments = Department::query()
            ->whereNotNull('foreman_user_id')
            ->orderBy('name')
            ->limit(4)
            ->get();

        if ($departments->isEmpty()) {
            return 0;
        }

        $scenarios = [
            [
                'code' => 'KRL-DEMO-COZULDU',
                'status' => TaskStatus::Cozuldu,
                'title' => 'Çözüldü — saha müdahalesi tamamlandı',
                'priority' => TaskPriority::Yuksek,
            ],
            [
                'code' => 'KRL-DEMO-ONAY',
                'status' => TaskStatus::OnayBekliyor,
                'title' => 'Onay bekliyor — kapanış için yönetici onayı',
                'priority' => TaskPriority::Kritik,
            ],
            [
                'code' => 'KRL-DEMO-KAPALI',
                'status' => TaskStatus::Kapatildi,
                'title' => 'Kapatıldı — vatandaş şikâyeti sonuçlandı',
                'priority' => TaskPriority::Normal,
            ],
            [
                'code' => 'KRL-DEMO-TAMAM',
                'status' => TaskStatus::Tamamlandi,
                'title' => 'Tamamlandı — rutin bakım kaydı',
                'priority' => TaskPriority::Normal,
            ],
        ];

        $created = 0;

        foreach ($departments->values() as $index => $department) {
            $scenario = $scenarios[$index] ?? null;
            if ($scenario === null) {
                break;
            }

            $now = now()->subDays(3 - $index);
            $assignedAt = $now->copy()->setTime(8, 0);
            $dispatchedAt = $now->copy()->setTime(8, 30);
            $resolvedAt = $now->copy()->setTime(11, 45);
            [$lat, $lng] = $this->coordinatesForIndex($index);

            Task::query()->updateOrCreate(
                ['task_code' => $scenario['code']],
                [
                    'title' => $scenario['title'],
                    'department_id' => $department->id,
                    'assignee_id' => $department->foreman_user_id,
                    'location' => 'Kırklareli — demo senaryo '.($index + 1),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'priority' => $scenario['priority'],
                    'status' => $scenario['status'],
                    'description' => "Demo görev — {$department->name} için {$scenario['status']->getLabel()} durumu.",
                    'arrival_photos' => ['task-arrival-photos/demo-map.jpg'],
                    'completion_photos' => ['task-completion-photos/demo-completion.jpg'],
                    'solution_note' => in_array($scenario['status'], [TaskStatus::Cozuldu, TaskStatus::OnayBekliyor, TaskStatus::Kapatildi, TaskStatus::Tamamlandi], true)
                        ? 'Demo çözüm notu: saha ekibi müdahale etti, vatandaş bilgilendirildi.'
                        : null,
                    'assigned_at' => $assignedAt,
                    'dispatched_at' => $dispatchedAt,
                    'resolved_at' => in_array($scenario['status'], [TaskStatus::Cozuldu, TaskStatus::OnayBekliyor, TaskStatus::Kapatildi, TaskStatus::Tamamlandi], true)
                        ? $resolvedAt
                        : null,
                    'resolve_minutes' => in_array($scenario['status'], [TaskStatus::Kapatildi, TaskStatus::Tamamlandi], true)
                        ? 225
                        : null,
                ],
            );

            $created++;
        }

        return $created;
    }

    /**
     * @return array{pending: int, approved: int, rejected: int}
     */
    private function seedApprovalRequests(): array
    {
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

        $onayTask = Task::query()->where('task_code', 'KRL-DEMO-ONAY')->first();
        $kapaliTask = Task::query()->where('task_code', 'KRL-DEMO-KAPALI')->first();
        $cozulduTask = Task::query()->where('task_code', 'KRL-DEMO-COZULDU')->first();

        $requester = User::query()->where('role', UserRole::Staff)->first();
        $approver = User::query()->where('role', UserRole::Admin)->first()
            ?? User::query()->where('role', UserRole::ViceMayor)->first();

        if ($requester === null) {
            return $stats;
        }

        if ($onayTask !== null) {
            ApprovalRequest::query()->updateOrCreate(
                [
                    'type' => 'task_close',
                    'approvable_type' => $onayTask->getMorphClass(),
                    'approvable_id' => $onayTask->id,
                    'status' => ApprovalRequest::STATUS_PENDING,
                ],
                [
                    'requested_by' => $requester->id,
                    'reason' => 'Demo: görev kapanışı için yönetici onayı bekleniyor.',
                    'payload' => ['source' => 'SeedFullDemoData', 'sample' => true],
                ],
            );
            $stats['pending']++;
        }

        if ($kapaliTask !== null && $approver !== null) {
            ApprovalRequest::query()->updateOrCreate(
                [
                    'type' => 'task_close',
                    'approvable_type' => $kapaliTask->getMorphClass(),
                    'approvable_id' => $kapaliTask->id,
                    'status' => ApprovalRequest::STATUS_APPROVED,
                ],
                [
                    'requested_by' => $requester->id,
                    'approved_by' => $approver->id,
                    'reason' => 'Demo: kapanış onaylandı.',
                    'payload' => ['source' => 'SeedFullDemoData', 'sample' => true],
                    'reviewed_at' => now()->subDay(),
                ],
            );
            $stats['approved']++;
        }

        if ($cozulduTask !== null && $approver !== null) {
            ApprovalRequest::query()->updateOrCreate(
                [
                    'type' => 'task_close',
                    'approvable_type' => $cozulduTask->getMorphClass(),
                    'approvable_id' => $cozulduTask->id,
                    'status' => ApprovalRequest::STATUS_REJECTED,
                ],
                [
                    'requested_by' => $requester->id,
                    'approved_by' => $approver->id,
                    'reason' => 'Demo: kanıt fotoğrafı yetersiz — yeniden saha kontrolü.',
                    'payload' => ['source' => 'SeedFullDemoData', 'sample' => true],
                    'reviewed_at' => now()->subHours(6),
                ],
            );
            $stats['rejected']++;
        }

        return $stats;
    }

    private function importClassifierSamples(): int
    {
        $path = base_path('ml/mudurluk-siniflandirici/data/belediye_sikayetleri.csv');
        if (! is_readable($path)) {
            return ClassifierTrainingSample::query()->count();
        }

        Artisan::call('classifier:import-samples', ['--file' => $path]);

        return ClassifierTrainingSample::query()->count();
    }

    private function seedPushTokens(): int
    {
        $created = 0;

        User::query()
            ->where('role', UserRole::Staff)
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->each(function (User $user, int $index) use (&$created): void {
                $token = 'ExponentPushToken[demo-'.$user->id.'-'.($index + 1).']';

                PushToken::query()->updateOrCreate(
                    ['token' => $token],
                    [
                        'user_id' => $user->id,
                        'platform' => $index % 2 === 0 ? 'ios' : 'android',
                        'device_name' => 'Demo Cihaz '.($index + 1),
                    ],
                );

                $created++;
            });

        return $created;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function coordinatesForIndex(int $index): array
    {
        $angle = (2 * M_PI * $index) / 8;
        $radius = 0.008;

        return [
            round(self::CENTER_LAT + $radius * cos($angle), 6),
            round(self::CENTER_LNG + ($radius * sin($angle)) / cos(deg2rad(self::CENTER_LAT)), 6),
        ];
    }
}
