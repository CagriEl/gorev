<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BelSystemSeeder extends Seeder
{
    public function run(): void
    {
        $fen = Department::query()->updateOrCreate(
            ['name' => 'Fen İşleri Müdürlüğü'],
            [
                'manager_name' => 'Ayşe Yılmaz',
                'manager_phone' => '0288 123 45 01',
                'foreman_name' => 'Mehmet Kaya',
                'foreman_phone' => '0288 123 45 02',
                'staff_count' => 42,
            ],
        );

        $temizlik = Department::query()->updateOrCreate(
            ['name' => 'Temizlik İşleri Müdürlüğü'],
            [
                'manager_name' => 'Zeynep Demir',
                'manager_phone' => '0288 123 45 10',
                'foreman_name' => 'Ali Çelik',
                'foreman_phone' => '0288 123 45 11',
                'staff_count' => 68,
            ],
        );

        $viceMayor = User::query()->updateOrCreate(
            ['email' => 'baskan.yardimcisi@kirklareli.bel.tr'],
            [
                'name' => 'Başkan Yardımcısı (Örnek)',
                'password' => Hash::make('password'),
                'role' => UserRole::ViceMayor,
                'department_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $fen->update(['vice_mayor_id' => $viceMayor->id]);
        $temizlik->update(['vice_mayor_id' => $viceMayor->id]);

        User::query()->updateOrCreate(
            ['email' => 'admin@kirklareli.bel.tr'],
            [
                'name' => 'Bel-Sistem Yönetici',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'department_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $staff = User::query()->updateOrCreate(
            ['email' => 'saha@kirklareli.bel.tr'],
            [
                'name' => 'Saha Personeli',
                'password' => Hash::make('password'),
                'role' => UserRole::Staff,
                'department_id' => $fen->id,
                'email_verified_at' => now(),
            ],
        );

        Task::query()->updateOrCreate(
            ['task_code' => 'KRL-1045'],
            [
                'title' => 'Merkez çevre yolu kaldırım onarımı',
                'department_id' => $fen->id,
                'assignee_id' => $staff->id,
                'location' => 'Lüleburgaz Caddesi, merkez',
                'latitude' => 41.7351,
                'longitude' => 27.2252,
                'priority' => TaskPriority::Yuksek,
                'status' => TaskStatus::Sahada,
                'description' => 'Örnek saha görevi — harita ve SLA testi için.',
                'assigned_at' => now()->subHours(3),
                'dispatched_at' => now()->subHours(2),
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
        );

        Task::query()->updateOrCreate(
            ['task_code' => 'KRL-1046'],
            [
                'title' => 'Park çim biçimi',
                'department_id' => $temizlik->id,
                'assignee_id' => null,
                'location' => 'İstasyon Meydanı',
                'latitude' => 41.7335,
                'longitude' => 27.2168,
                'priority' => TaskPriority::Normal,
                'status' => TaskStatus::Bekliyor,
                'description' => 'Haftalık bakım.',
                'assigned_at' => now()->subDay(),
                'dispatched_at' => null,
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
        );

        Task::query()->updateOrCreate(
            ['task_code' => 'KRL-1047'],
            [
                'title' => 'Kritik kanal tıkanıklığı',
                'department_id' => $fen->id,
                'assignee_id' => $staff->id,
                'location' => 'Yıldırım Mahallesi',
                'latitude' => 41.7400,
                'longitude' => 27.2300,
                'priority' => TaskPriority::Kritik,
                'status' => TaskStatus::Yonlendirildi,
                'description' => 'Örnek acil müdahale.',
                'assigned_at' => now()->subMinutes(30),
                'dispatched_at' => now()->subMinutes(10),
                'resolved_at' => null,
                'resolve_minutes' => null,
            ],
        );

        Task::query()->updateOrCreate(
            ['task_code' => 'KRL-1000'],
            [
                'title' => 'Tamamlanan örnek görev (SLA)',
                'department_id' => $temizlik->id,
                'assignee_id' => null,
                'location' => 'Örnek adres',
                'latitude' => 41.72,
                'longitude' => 27.20,
                'priority' => TaskPriority::Normal,
                'status' => TaskStatus::Tamamlandi,
                'description' => 'Örnek tamamlanmış görev.',
                'assigned_at' => now()->subDays(7)->setHour(9),
                'dispatched_at' => now()->subDays(7)->setHour(9)->addMinutes(15),
                'resolved_at' => now()->subDays(7)->setHour(9)->addMinutes(90),
                'resolve_minutes' => 90,
            ],
        );
    }
}
