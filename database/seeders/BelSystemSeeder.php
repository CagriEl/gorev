<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
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

        User::query()->updateOrCreate(
            ['email' => 'saha@kirklareli.bel.tr'],
            [
                'name' => 'Saha Personeli',
                'password' => Hash::make('password'),
                'role' => UserRole::Staff,
                'department_id' => $fen->id,
                'email_verified_at' => now(),
            ],
        );
    }
}
