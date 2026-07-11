<?php

namespace App\Services;

use App\Models\ClassifierTrainingSample;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class ClassifierModelTrainer
{
    public function __construct(
        protected MudurlukClassifierService $slugger,
    ) {}

    /**
     * @return array{ready: bool, status: string, message?: string}
     */
    public function healthCheck(): array
    {
        $baseUrl = rtrim((string) config('services.mudurluk_classifier.url'), '/');
        if ($baseUrl === '') {
            return [
                'ready' => false,
                'status' => 'not_configured',
                'message' => 'ML_CLASSIFIER_URL tanımlı değil.',
            ];
        }

        try {
            $response = Http::timeout(3)->get("{$baseUrl}/health");
            $payload = $response->json();

            return [
                'ready' => (bool) ($payload['ready'] ?? false),
                'status' => (string) ($payload['status'] ?? 'unknown'),
                'message' => $response->successful() ? null : 'Servis yanıt vermedi.',
            ];
        } catch (\Throwable $exception) {
            return [
                'ready' => false,
                'status' => 'offline',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function modelDirectory(): string
    {
        return (string) config('services.mudurluk_classifier.model_dir',
            base_path('ml/mudurluk-siniflandirici/kaydedilen_model'));
    }

    public function lastTrainedAt(): ?string
    {
        $configFile = $this->modelDirectory().'/config.json';
        if (! File::exists($configFile)) {
            return null;
        }

        return date('Y-m-d H:i:s', File::lastModified($configFile));
    }

    public function activeSampleCount(): int
    {
        return ClassifierTrainingSample::query()->where('is_active', true)->count();
    }

    /**
     * Panelden girilen örnekleri eğitim CSV'sine yazar.
     */
    public function exportPanelSamplesToCsv(): string
    {
        $projectPath = $this->projectPath();
        $exportDir = $projectPath.'/data/panel_export';
        if (! File::isDirectory($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $path = $exportDir.'/panel_samples.csv';
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new RuntimeException('CSV dosyası oluşturulamadı.');
        }

        fputcsv($handle, ['sikayet_metni', 'mudurluk_slug']);

        ClassifierTrainingSample::query()
            ->where('is_active', true)
            ->with('department:id,name')
            ->orderBy('id')
            ->each(function (ClassifierTrainingSample $sample) use ($handle): void {
                $slug = $sample->mudurluk_slug;
                if ($sample->department) {
                    $slug = $this->slugger->slugify($sample->department->name);
                }
                fputcsv($handle, [
                    trim($sample->sikayet_metni),
                    $slug,
                ]);
            });

        fclose($handle);

        return $path;
    }

    /**
     * Veriyi hazırlayıp BERTurk eğitimini başlatır.
     *
     * @return array{success: bool, output: string}
     */
    public function retrain(): array
    {
        // Web isteği 30 sn limitine takılmaması için (panel / kuyruk / artisan)
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $python = (string) config('services.mudurluk_classifier.python_path');
        $project = $this->projectPath();
        $processTimeout = (int) config('services.mudurluk_classifier.retrain_timeout', 1200);

        if (! File::isFile($python)) {
            throw new RuntimeException("Python bulunamadı: {$python}");
        }

        $this->exportPanelSamplesToCsv();

        $commands = [
            [$python, 'data_loader.py', '--merge-panel'],
            [$python, 'train.py'],
        ];

        $combinedOutput = '';

        foreach ($commands as $command) {
            $result = Process::path($project)
                ->timeout($processTimeout)
                ->run($command);

            $combinedOutput .= implode(' ', $command)."\n".$result->output()."\n".$result->errorOutput()."\n";

            if (! $result->successful()) {
                return [
                    'success' => false,
                    'output' => $combinedOutput,
                ];
            }
        }

        return [
            'success' => true,
            'output' => $combinedOutput,
        ];
    }

    protected function projectPath(): string
    {
        return (string) config('services.mudurluk_classifier.project_path',
            base_path('ml/mudurluk-siniflandirici'));
    }
}
