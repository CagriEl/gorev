<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Kırklareli merkezli harita gösterimi için örnek görevler (Saha Operasyonu / Map).
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $fen = Department::query()->where('name', 'Fen İşleri Müdürlüğü')->first();
        $temizlik = Department::query()->where('name', 'Temizlik İşleri Müdürlüğü')->first();
        $staff = User::query()->where('email', 'saha@kirklareli.bel.tr')->first();

        if ($fen === null || $temizlik === null) {
            $this->command?->warn('TaskSeeder: Önce BelSystemSeeder çalıştırılmalı (müdürlük kayıtları yok).');

            return;
        }

        $assigneeId = $staff?->id;

        $rows = [
            [
                'task_code' => 'KRL-MAP-01',
                'title' => 'Karakaş Mahallesi kaldırım ve aydınlatma kontrolü',
                'location' => 'Karakaş Mahallesi, 100. Yıl Caddesi',
                'latitude' => 41.7350,
                'longitude' => 27.2250,
                'status' => TaskStatus::Sahada,
                'priority' => TaskPriority::Yuksek,
                'department_id' => $fen->id,
                'description' => '100. Yıl Caddesi üzerinde kaldırım ve aydınlatma arızası kontrolü.',
                'assigned_at' => now()->subHours(4),
                'dispatched_at' => now()->subHours(3),
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
            [
                'task_code' => 'KRL-MAP-02',
                'title' => 'İstasyon Caddesi çevre düzenlemesi',
                'location' => 'İstasyon Caddesi, Tren Garı Mevkii',
                'latitude' => 41.7280,
                'longitude' => 27.2150,
                'status' => TaskStatus::Tamamlandi,
                'priority' => TaskPriority::Normal,
                'department_id' => $temizlik->id,
                'description' => 'Tren garı çevresi temizlik ve düzenleme tamamlandı.',
                'assigned_at' => now()->subDays(2)->setHour(8)->setMinute(0),
                'dispatched_at' => now()->subDays(2)->setHour(8)->setMinute(20),
                'resolved_at' => now()->subDays(2)->setHour(11)->setMinute(30),
                'resolve_minutes' => 190,
            ],
            [
                'task_code' => 'KRL-MAP-03',
                'title' => 'Bademlik Mahallesi yol bakım ihbarı',
                'location' => 'Bademlik Mahallesi, Eriklice Yolu',
                'latitude' => 41.7420,
                'longitude' => 27.2100,
                'status' => TaskStatus::Bekliyor,
                'priority' => TaskPriority::Normal,
                'department_id' => $fen->id,
                'description' => 'Eriklice Yolu üzerinde çukur ve işaretleme talebi.',
                'assigned_at' => now()->subHours(12),
                'dispatched_at' => null,
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
            [
                'task_code' => 'KRL-MAP-04',
                'title' => 'OSB bölgesi altyapı koordinasyonu',
                'location' => 'Yaylaköy Yolu, OSB Bölgesi',
                'latitude' => 41.7100,
                'longitude' => 27.2500,
                'status' => TaskStatus::Yonlendirildi,
                'priority' => TaskPriority::Yuksek,
                'department_id' => $fen->id,
                'description' => 'OSB girişi yakınında koordinasyon gerektiren altyapı çalışması.',
                'assigned_at' => now()->subHours(6),
                'dispatched_at' => now()->subHours(2),
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
            [
                'task_code' => 'KRL-MAP-05',
                'title' => 'Cumhuriyet Caddesi yoğun bölge denetimi',
                'location' => 'Cumhuriyet Caddesi, Valilik Önü',
                'latitude' => 41.7340,
                'longitude' => 27.2210,
                'status' => TaskStatus::Sahada,
                'priority' => TaskPriority::Kritik,
                'department_id' => $temizlik->id,
                'description' => 'Valilik önü ve Cumhuriyet Caddesi günlük saha denetimi.',
                'assigned_at' => now()->subHours(2),
                'dispatched_at' => now()->subHour(),
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
        ];

        foreach ($rows as $row) {
            Task::query()->updateOrCreate(
                ['task_code' => $row['task_code']],
                array_merge($row, ['assignee_id' => $assigneeId]),
            );
        }
    }
}
