<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Jobs\RetrainClassifierModelJob;
use App\Models\ClassifierTrainingSample;
use App\Services\ClassifierModelTrainer;
use App\Services\MudurlukClassifierService;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ClassifierLabPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Müdürlük sınıflandırıcı';

    protected static ?string $title = 'Müdürlük sınıflandırıcı';

    protected static ?string $navigationGroup = 'Yapay Zeka';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.classifier-lab';

    protected static ?string $slug = 'mudurluk-siniflandirici';

    public string $testText = '';

    /** @var array<string, mixed>|null */
    public ?array $prediction = null;

    /** @var array<string, mixed> */
    public array $serviceHealth = [];

    public int $activeSampleCount = 0;

    public ?string $lastTrainedAt = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && in_array($user->role, [UserRole::Admin, UserRole::ViceMayor], true);
    }

    public function mount(): void
    {
        $trainer = app(ClassifierModelTrainer::class);
        $this->serviceHealth = $trainer->healthCheck();
        $this->activeSampleCount = $trainer->activeSampleCount();
        $this->lastTrainedAt = $trainer->lastTrainedAt();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $status = $this->serviceHealth['ready'] ?? false
            ? 'Model servisi çalışıyor'
            : 'Model servisi kapalı veya yüklenmedi';

        return $status;
    }

    public function runPrediction(): void
    {
        $this->validate([
            'testText' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $result = app(MudurlukClassifierService::class)->predict($this->testText);

        if ($result === null) {
            Notification::make()
                ->title('Tahmin yapılamadı')
                ->body('ML servisi yanıt vermedi. ml/mudurluk-siniflandirici/scripts/start_api.sh çalışıyor mu kontrol edin.')
                ->danger()
                ->send();

            $this->prediction = null;

            return;
        }

        $this->prediction = $result;

        Notification::make()
            ->title('Tahmin tamamlandı')
            ->success()
            ->send();
    }

    public function addPredictionAsSample(): void
    {
        if ($this->prediction === null || empty($this->prediction['department_id'])) {
            Notification::make()
                ->title('Örnek eklenemedi')
                ->body('Önce başarılı bir tahmin alın ve müdürlük eşleşmesi olsun.')
                ->warning()
                ->send();

            return;
        }

        ClassifierTrainingSample::query()->create([
            'sikayet_metni' => $this->testText,
            'department_id' => $this->prediction['department_id'],
            'mudurluk_slug' => $this->prediction['department_slug'],
            'is_active' => true,
            'notes' => 'Tahmin laboratuvarından eklendi',
            'created_by' => auth()->id(),
        ]);

        $this->activeSampleCount = app(ClassifierModelTrainer::class)->activeSampleCount();

        Notification::make()
            ->title('Eğitim örneği kaydedildi')
            ->body('Modeli yeniden eğittiğinizde bu metin de kullanılacak.')
            ->success()
            ->send();
    }

    public function retrainModel(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (config('services.mudurluk_classifier.retrain_via_queue', true)) {
            RetrainClassifierModelJob::dispatch(auth()->id());

            Notification::make()
                ->title('Eğitim kuyruğa alındı')
                ->body('Arka planda çalışacak. `php artisan queue:work` açık olmalı. Tamamlanınca panel bildirimi gelir (1–5 dk).')
                ->success()
                ->send();

            return;
        }

        set_time_limit(0);
        ini_set('max_execution_time', '0');

        try {
            $result = app(ClassifierModelTrainer::class)->retrain();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Eğitim başlatılamadı')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        if (! $result['success']) {
            Notification::make()
                ->title('Eğitim hatası')
                ->body(mb_substr($result['output'], 0, 500))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->lastTrainedAt = app(ClassifierModelTrainer::class)->lastTrainedAt();
        $this->serviceHealth = app(ClassifierModelTrainer::class)->healthCheck();

        Notification::make()
            ->title('Model yeniden eğitildi')
            ->body('API servisini yeniden başlatmanız gerekebilir (uvicorn).')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retrain')
                ->label('Modeli yeniden eğit')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('BERTurk modelini yeniden eğit')
                ->modalDescription('Eğitim 1–5 dakika sürebilir. Varsayılan olarak arka planda (kuyruk) çalışır; sayfa hemen kapanır. Kuyruk yoksa .env içinde ML_CLASSIFIER_RETRAIN_VIA_QUEUE=false yapın veya terminalde: php artisan classifier:retrain')
                ->action(fn () => $this->retrainModel())
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
        ];
    }
}
