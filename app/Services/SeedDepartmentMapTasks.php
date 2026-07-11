<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Task;
use Illuminate\Support\Str;

final class SeedDepartmentMapTasks
{
    /** Kırklareli merkez (Saha Haritası varsayılan görünüm). */
    private const CENTER_LAT = 41.7333;

    private const CENTER_LNG = 27.2253;

    /** Müdürlük pinleri merkez etrafında ~1,2 km yarıçapta dağılır. */
    private const SPREAD_RADIUS = 0.011;

    /**
     * @return array{created: int, updated: int, skipped: int, total: int}
     */
    public function seed(bool $onlyMissing = false): array
    {
        $departments = Department::query()->orderBy('name')->get();
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => $departments->count()];

        $departments->each(function (Department $department, int $index) use (&$stats, $onlyMissing, $departments): void {
            $taskCode = $this->taskCodeFor($department);

            if ($onlyMissing && Task::query()->where('task_code', $taskCode)->exists()) {
                $stats['skipped']++;

                return;
            }

            [$lat, $lng] = $this->coordinatesForIndex($index, $departments->count());
            $payload = $this->payloadForDepartment($department, $index, $lat, $lng);

            $existing = Task::query()->where('task_code', $taskCode)->first();
            Task::query()->updateOrCreate(['task_code' => $taskCode], $payload);

            if ($existing === null) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }
        });

        return $stats;
    }

    public function taskCodeFor(Department $department): string
    {
        return 'KRL-HARITA-'.$department->id;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function coordinatesForIndex(int $index, int $total): array
    {
        if ($total <= 1) {
            return [self::CENTER_LAT, self::CENTER_LNG];
        }

        $angle = (2 * M_PI * $index) / $total;
        $lat = self::CENTER_LAT + self::SPREAD_RADIUS * cos($angle);
        $lng = self::CENTER_LNG + (self::SPREAD_RADIUS * sin($angle)) / cos(deg2rad(self::CENTER_LAT));

        return [round($lat, 6), round($lng, 6)];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadForDepartment(Department $department, int $index, float $lat, float $lng): array
    {
        $shortName = Str::before($department->name, ' Müdürlüğü');
        $location = $this->locationLabel($index);

        [$status, $timestamps, $assigneeId] = $this->statusBundle($department, $index);

        return array_merge([
            'title' => "{$shortName} — saha görevi",
            'department_id' => $department->id,
            'assignee_id' => $assigneeId,
            'location' => $location,
            'latitude' => $lat,
            'longitude' => $lng,
            'priority' => $this->priorityForIndex($index),
            'status' => $status,
            'description' => "Harita gösterimi için örnek görev — {$department->name}.",
            'arrival_photos' => ['task-arrival-photos/demo-map.jpg'],
        ], $timestamps);
    }

    private function locationLabel(int $index): string
    {
        $spots = [
            'Cumhuriyet Caddesi, Valilik önü',
            'Hükümet Caddesi',
            'Karacaibrahim Mahallesi',
            'Karakaş Mahallesi, 100. Yıl Caddesi',
            'İstasyon Caddesi, Tren Garı',
            'Bademlik Mahallesi',
            'Kocahıdır Mahallesi',
            'Yayla Mahallesi',
            'Kırıkköy Mahallesi',
            'Üniversite Kampüsü çevresi',
            'Atatürk Bulvarı',
            'Liman Bölgesi',
            'Sanayi Sitesi girişi',
            'OSB bölgesi',
            'Merkez Pazar yeri',
            'Stadyum çevresi',
            'Devlet Hastanesi önü',
            'Belediye hizmet binası',
            'Kent meydanı',
            'Sahil yolu',
            'Eriklice yolu kavşağı',
        ];

        return 'Kırklareli — '.($spots[$index % count($spots)] ?? 'merkez');
    }

    private function priorityForIndex(int $index): TaskPriority
    {
        return match ($index % 5) {
            0 => TaskPriority::Kritik,
            1, 2 => TaskPriority::Yuksek,
            default => TaskPriority::Normal,
        };
    }

    /**
     * @return array{0: TaskStatus, 1: array<string, mixed>, 2: int|null}
     */
    private function statusBundle(Department $department, int $index): array
    {
        $foremanId = $department->foreman_user_id;
        $now = now();

        if ($foremanId === null) {
            return [
                TaskStatus::Bekliyor,
                [
                    'assigned_at' => null,
                    'dispatched_at' => null,
                    'resolved_at' => null,
                    'resolve_minutes' => null,
                ],
                null,
            ];
        }

        return match ($index % 3) {
            1 => [
                TaskStatus::Yonlendirildi,
                [
                    'assigned_at' => $now->copy()->subHours(3),
                    'dispatched_at' => null,
                    'resolved_at' => null,
                    'resolve_minutes' => null,
                ],
                $foremanId,
            ],
            2 => [
                TaskStatus::Sahada,
                [
                    'assigned_at' => $now->copy()->subHours(5),
                    'dispatched_at' => $now->copy()->subHours(2),
                    'resolved_at' => null,
                    'resolve_minutes' => null,
                ],
                $foremanId,
            ],
            default => [
                TaskStatus::Bekliyor,
                [
                    'assigned_at' => null,
                    'dispatched_at' => null,
                    'resolved_at' => null,
                    'resolve_minutes' => null,
                ],
                null,
            ],
        };
    }
}
