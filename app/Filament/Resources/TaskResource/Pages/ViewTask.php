<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use App\Models\ApprovalRequest;
use App\Models\Task;
use App\Support\Geo;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\ActionSize;

class ViewTask extends ViewRecord
{
    /** Saha tamamlama: görev koordinatına en fazla bu kadar metre (GPS doğrulaması). */
    public const FIELD_COMPLETE_MAX_METERS = 100;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        $touch = ['class' => '!min-h-[3.5rem] !px-6 !py-4 !text-base'];

        return [
            Actions\Action::make('directions')
                ->label('Yol tarifi')
                ->tooltip('Google Haritalar’da bu göreve rota aç')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->size(ActionSize::ExtraLarge)
                ->extraAttributes($touch)
                ->url(function (): string {
                    /** @var Task $record */
                    $record = $this->getRecord();

                    if ($record->latitude !== null && $record->longitude !== null) {
                        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode(
                            (string) $record->latitude.','.(string) $record->longitude,
                        );
                    }

                    $location = trim((string) $record->location);

                    return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode(
                        $location !== '' ? $location : 'Kırklareli',
                    );
                })
                ->openUrlInNewTab()
                ->visible(function (): bool {
                    /** @var Task $record */
                    $record = $this->getRecord();

                    if ($record->latitude !== null && $record->longitude !== null) {
                        return true;
                    }

                    return filled(trim((string) $record->location));
                }),
            Actions\Action::make('completeOnSite')
                ->label('Görevi tamamla')
                ->tooltip('Cihaz konumunuz görev noktasına '.self::FIELD_COMPLETE_MAX_METERS.' m içinde olmalıdır')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size(ActionSize::ExtraLarge)
                ->extraAttributes($touch)
                ->visible(function (): bool {
                    /** @var Task $record */
                    $record = $this->getRecord();

                    return ! $record->isClosed()
                        && $record->latitude !== null
                        && $record->longitude !== null;
                })
                ->alpineClickHandler(
                    'async ($event) => { $event.preventDefault(); $event.stopPropagation(); if (!navigator.geolocation) { const F = window.FilamentNotification; if (typeof F === \'function\') { new F().title(\'Konum gerekli\').body(\'Bu tarayıcı konum API desteklemiyor.\').danger().send(); } return; } navigator.geolocation.getCurrentPosition((pos) => $wire.completeFromField(pos.coords.latitude, pos.coords.longitude), () => { const F = window.FilamentNotification; if (typeof F === \'function\') { new F().title(\'Konum alınamadı\').body(\'Konum izni verin veya GPS açık olsun; ardından tekrar deneyin.\').warning().send(); } }, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 }); }',
                ),
            Actions\Action::make('approveClosure')
                ->label('Kapanışı onayla')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->size(ActionSize::ExtraLarge)
                ->extraAttributes($touch)
                ->requiresConfirmation()
                ->action(fn (): mixed => $this->approveClosure())
                ->visible(fn (): bool => $this->canReviewClosure()),
            Actions\Action::make('rejectClosure')
                ->label('Kapanışı reddet')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->size(ActionSize::ExtraLarge)
                ->extraAttributes($touch)
                ->requiresConfirmation()
                ->action(fn (): mixed => $this->rejectClosure())
                ->visible(fn (): bool => $this->canReviewClosure()),
            Actions\EditAction::make()
                ->size(ActionSize::ExtraLarge)
                ->extraAttributes($touch),
        ];
    }

    public function completeFromField(float $userLat, float $userLng): void
    {
        /** @var Task $task */
        $task = $this->getRecord();

        $this->authorize('update', $task);

        if ($task->isClosed()) {
            Notification::make()
                ->title('Görev zaten tamamlanmış')
                ->warning()
                ->send();

            return;
        }

        if ($task->latitude === null || $task->longitude === null) {
            Notification::make()
                ->title('Görevde koordinat yok')
                ->body('Saha doğrulaması için görevde enlem/boylam tanımlı olmalıdır.')
                ->warning()
                ->send();

            return;
        }

        $distance = Geo::distanceMeters(
            $userLat,
            $userLng,
            (float) $task->latitude,
            (float) $task->longitude,
        );

        if ($distance > self::FIELD_COMPLETE_MAX_METERS) {
            Notification::make()
                ->title('Görevi tamamlamak için saha konumunda olmalısınız')
                ->body('Görev noktasına yaklaşık '.(int) round($distance).' m uzaktasınız (en fazla '.self::FIELD_COMPLETE_MAX_METERS.' m).')
                ->danger()
                ->send();

            return;
        }

        if (empty($task->arrival_photos)) {
            Notification::make()
                ->title('Varış fotoğrafı gerekli')
                ->body('Görevi kapatmadan önce görev yerine varış fotoğrafı yükleyin.')
                ->warning()
                ->send();

            return;
        }

        if (empty($task->completion_photos)) {
            Notification::make()
                ->title('Görev sonrası fotoğraf gerekli')
                ->body('Görevi kapatmadan önce görev sonrası fotoğrafı yükleyin.')
                ->warning()
                ->send();

            return;
        }

        $task->status = TaskStatus::Cozuldu;

        if ($task->resolved_at === null) {
            $task->resolved_at = now();
        }
        if ($task->assigned_at === null) {
            $task->assigned_at = now();
        }
        if ($task->assigned_at && $task->resolved_at) {
            $task->resolve_minutes = (int) Carbon::parse($task->assigned_at)
                ->diffInMinutes(Carbon::parse($task->resolved_at));
        }
        $task->field_completed_at = now();
        $task->field_completion_distance_m = round($distance, 2);
        $task->field_completion_latitude = $userLat;
        $task->field_completion_longitude = $userLng;
        $task->solution_note ??= 'Sahada tamamlandı.';

        if ($this->requiresSecondApproval($task)) {
            $task->status = TaskStatus::OnayBekliyor;
            $task->save();
            ApprovalRequest::query()->create([
                'type' => 'task_close',
                'status' => ApprovalRequest::STATUS_PENDING,
                'approvable_type' => $task->getMorphClass(),
                'approvable_id' => $task->id,
                'requested_by' => auth()->id(),
                'reason' => 'Kritik görev veya SLA ihlali nedeniyle ikinci onay gerekli.',
                'payload' => [
                    'distance_m' => round($distance, 2),
                    'status' => $task->status->value,
                ],
            ]);

            Notification::make()
                ->title('Görev onaya gönderildi')
                ->body('Kritik görev/SLA ihlali nedeniyle ikinci onay bekleniyor.')
                ->warning()
                ->send();
        } else {
            $task->status = TaskStatus::Kapatildi;
            $task->save();
            Notification::make()
                ->title('Görev kapatıldı')
                ->success()
                ->send();
        }

        $this->record->refresh();
        $this->dispatch('$refresh');
    }

    protected function requiresSecondApproval(Task $task): bool
    {
        return $task->priority->value === 'kritik' || $task->slaWithinTarget(Task::SLA_TARGET_MINUTES) === false;
    }

    protected function canReviewClosure(): bool
    {
        $task = $this->getRecord();
        $user = auth()->user();
        if (! $user || $task->status !== TaskStatus::OnayBekliyor) {
            return false;
        }

        return $user->isAdmin() || ($user->isViceMayor() && in_array($task->department_id, $user->managedDepartmentIds(), true));
    }

    public function approveClosure(): void
    {
        /** @var Task $task */
        $task = $this->getRecord();
        if (! $this->canReviewClosure()) {
            abort(403);
        }

        $task->status = TaskStatus::Kapatildi;
        $task->save();
        $task->approvalRequests()
            ->where('type', 'task_close')
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->latest('id')
            ->limit(1)
            ->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        Notification::make()->title('Kapanış onaylandı')->success()->send();
        $this->record->refresh();
        $this->dispatch('$refresh');
    }

    public function rejectClosure(): void
    {
        /** @var Task $task */
        $task = $this->getRecord();
        if (! $this->canReviewClosure()) {
            abort(403);
        }
        $task->status = TaskStatus::Yonlendirildi;
        $task->save();
        $task->approvalRequests()
            ->where('type', 'task_close')
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->latest('id')
            ->limit(1)
            ->update([
                'status' => ApprovalRequest::STATUS_REJECTED,
                'approved_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

        Notification::make()->title('Kapanış reddedildi')->warning()->send();
        $this->record->refresh();
        $this->dispatch('$refresh');
    }
}
