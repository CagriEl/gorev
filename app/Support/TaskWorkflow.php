<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Validation\ValidationException;

final class TaskWorkflow
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function transitions(): array
    {
        return [
            TaskStatus::Bekliyor->value => [TaskStatus::Yonlendirildi->value],
            TaskStatus::Yonlendirildi->value => [TaskStatus::Sahada->value, TaskStatus::Bekliyor->value],
            TaskStatus::Sahada->value => [TaskStatus::Cozuldu->value, TaskStatus::Yonlendirildi->value],
            TaskStatus::Cozuldu->value => [TaskStatus::OnayBekliyor->value, TaskStatus::Kapatildi->value],
            TaskStatus::OnayBekliyor->value => [TaskStatus::Kapatildi->value, TaskStatus::Yonlendirildi->value],
            TaskStatus::Kapatildi->value => [],
            TaskStatus::Tamamlandi->value => [],
        ];
    }

    public static function assertTransition(TaskStatus $from, TaskStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = self::transitions()[$from->value] ?? [];
        if (! in_array($to->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'Bu durum geçişine izin verilmiyor.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function assertRequiredFields(array $data, ?Task $task = null): void
    {
        $status = TaskStatus::tryFrom((string) ($data['status'] ?? $task?->status?->value ?? ''));
        if (! $status) {
            return;
        }

        // Görev ilk oluşturulurken varış fotoğrafı zorunludur.
        if ($task === null && empty($data['arrival_photos'])) {
            throw ValidationException::withMessages([
                'arrival_photos' => 'Görev oluştururken görev yerine varış fotoğrafı zorunludur.',
            ]);
        }

        if ($status === TaskStatus::Yonlendirildi && empty($data['assignee_id'])) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Yönlendirildi durumunda atanan personel zorunludur.',
            ]);
        }

        if ($status === TaskStatus::Sahada && empty($data['dispatched_at'])) {
            throw ValidationException::withMessages([
                'dispatched_at' => 'Sahada durumunda sahaya çıkış tarihi zorunludur.',
            ]);
        }

        if (in_array($status, [TaskStatus::Sahada, TaskStatus::Cozuldu, TaskStatus::OnayBekliyor, TaskStatus::Kapatildi], true)) {
            $arrivalPhotos = $data['arrival_photos'] ?? $task?->arrival_photos;
            if (empty($arrivalPhotos)) {
                throw ValidationException::withMessages([
                    'arrival_photos' => 'Görev yerine varış fotoğrafı zorunludur.',
                ]);
            }
        }

        if (in_array($status, [TaskStatus::Cozuldu, TaskStatus::OnayBekliyor, TaskStatus::Kapatildi], true)) {
            $resolvedAt = $data['resolved_at'] ?? $task?->resolved_at;
            if (empty($resolvedAt)) {
                throw ValidationException::withMessages([
                    'resolved_at' => 'Çözüldü/Kapatıldı durumunda tamamlanma tarihi zorunludur.',
                ]);
            }
            $solutionNote = $data['solution_note'] ?? $task?->solution_note;
            if (empty($solutionNote)) {
                throw ValidationException::withMessages([
                    'solution_note' => 'Kapanış için çözüm notu zorunludur.',
                ]);
            }
            $completionPhotos = $data['completion_photos'] ?? $task?->completion_photos;
            if (empty($completionPhotos)) {
                throw ValidationException::withMessages([
                    'completion_photos' => 'Görev sonrası fotoğraf zorunludur.',
                ]);
            }
        }

        $assigned = $data['assigned_at'] ?? $task?->assigned_at;
        $dispatched = $data['dispatched_at'] ?? $task?->dispatched_at;
        $resolved = $data['resolved_at'] ?? $task?->resolved_at;
        if ($assigned && $dispatched && $resolved) {
            $assignedAt = \Carbon\Carbon::parse($assigned);
            $dispatchedAt = \Carbon\Carbon::parse($dispatched);
            $resolvedAt = \Carbon\Carbon::parse($resolved);
            if ($assignedAt->greaterThan($dispatchedAt) || $dispatchedAt->greaterThan($resolvedAt)) {
                throw ValidationException::withMessages([
                    'resolved_at' => 'Tarih sırası hatalı: Atanma <= Sahaya çıkış <= Tamamlanma olmalıdır.',
                ]);
            }
        }
    }
}
