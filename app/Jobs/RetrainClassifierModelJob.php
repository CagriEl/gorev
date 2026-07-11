<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ClassifierModelTrainer;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RetrainClassifierModelJob implements ShouldQueue
{
    use Queueable;

    /** Eğitim uzun sürebilir (saniye). */
    public int $timeout = 1200;

    public function __construct(
        public ?int $requestedByUserId = null,
    ) {}

    public function handle(ClassifierModelTrainer $trainer): void
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $result = $trainer->retrain();

        if (! $result['success']) {
            Log::error('classifier.retrain.failed', ['output' => $result['output']]);
            $this->notifyRequester(
                'Model eğitimi başarısız',
                'Ayrıntılar için storage/logs/laravel.log dosyasına bakın.',
                'danger',
            );

            return;
        }

        $this->notifyRequester(
            'Model yeniden eğitildi',
            'Gerekirse ML API servisini yeniden başlatın (scripts/start_api.sh).',
            'success',
        );
    }

    protected function notifyRequester(string $title, string $body, string $status): void
    {
        if ($this->requestedByUserId === null) {
            return;
        }

        $user = User::query()->find($this->requestedByUserId);
        if ($user === null) {
            return;
        }

        $notification = Notification::make()->title($title)->body($body);
        match ($status) {
            'success' => $notification->success(),
            'danger' => $notification->danger(),
            default => $notification->warning(),
        };
        $notification->sendToDatabase($user);
    }
}
