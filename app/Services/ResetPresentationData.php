<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\ClassifierTrainingSample;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Support\PresentationDemo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

final class ResetPresentationData
{
    /**
     * Operasyonel demo verisini temizler; müdürlük ve kullanıcı kayıtlarına dokunmaz.
     *
     * @return array{
     *     tasks: int,
     *     approvals: int,
     *     training_samples: int,
     *     activity_logs: int,
     *     api_tokens: int,
     *     upload_dirs_cleared: list<string>,
     *     demo_department: int,
     *     demo_users: int
     * }
     */
    public function reset(
        bool $clearTrainingSamples = true,
        bool $clearActivityLog = true,
        bool $clearApiTokens = true,
        bool $clearUploads = true,
        bool $clearDemoOrganisation = true,
    ): array {
        return DB::transaction(function () use ($clearTrainingSamples, $clearActivityLog, $clearApiTokens, $clearUploads, $clearDemoOrganisation): array {
            $taskCount = Task::withTrashed()->count();
            Task::withTrashed()->forceDelete();

            $approvalCount = ApprovalRequest::query()->count();
            ApprovalRequest::query()->delete();

            $trainingCount = 0;
            if ($clearTrainingSamples) {
                $trainingCount = ClassifierTrainingSample::query()->count();
                ClassifierTrainingSample::query()->delete();
            }

            $activityCount = 0;
            if ($clearActivityLog) {
                $activityCount = Activity::query()->count();
                Activity::query()->delete();
            }

            $tokenCount = 0;
            if ($clearApiTokens && DB::getSchemaBuilder()->hasTable('personal_access_tokens')) {
                $tokenCount = (int) DB::table('personal_access_tokens')->count();
                DB::table('personal_access_tokens')->delete();
            }

            $uploadDirs = $clearUploads ? $this->clearTaskUploadDirectories() : [];

            $demoOrg = $clearDemoOrganisation
                ? $this->clearDemoOrganisation()
                : ['demo_department' => 0, 'demo_users' => 0];

            return [
                'tasks' => $taskCount,
                'approvals' => $approvalCount,
                'training_samples' => $trainingCount,
                'activity_logs' => $activityCount,
                'api_tokens' => $tokenCount,
                'upload_dirs_cleared' => $uploadDirs,
                'demo_department' => $demoOrg['demo_department'],
                'demo_users' => $demoOrg['demo_users'],
            ];
        });
    }

    /**
     * @return array{demo_department: int, demo_users: int}
     */
    public function clearDemoOrganisation(): array
    {
        $department = Department::query()
            ->where('name', PresentationDemo::DEPARTMENT_NAME)
            ->first();

        if ($department === null) {
            $removedUsers = User::query()
                ->whereIn('email', [PresentationDemo::MANAGER_EMAIL, PresentationDemo::FOREMAN_EMAIL])
                ->delete();

            return ['demo_department' => 0, 'demo_users' => $removedUsers];
        }

        Task::withTrashed()->where('department_id', $department->id)->forceDelete();

        $removedUsers = User::query()
            ->where('department_id', $department->id)
            ->orWhereIn('email', [PresentationDemo::MANAGER_EMAIL, PresentationDemo::FOREMAN_EMAIL])
            ->delete();

        $department->delete();

        return ['demo_department' => 1, 'demo_users' => $removedUsers];
    }

    /**
     * @return list<string>
     */
    private function clearTaskUploadDirectories(): array
    {
        $cleared = [];
        $disk = Storage::disk('public');

        foreach ([
            'task-arrival-photos',
            'task-completion-photos',
            'close-proof-photos',
        ] as $dir) {
            if (! $disk->exists($dir)) {
                continue;
            }

            foreach ($disk->allFiles($dir) as $file) {
                $disk->delete($file);
            }

            $cleared[] = $dir;
        }

        $livewireTmp = storage_path('app/livewire-tmp');
        if (File::isDirectory($livewireTmp)) {
            File::cleanDirectory($livewireTmp);
            $cleared[] = 'livewire-tmp';
        }

        return $cleared;
    }
}
