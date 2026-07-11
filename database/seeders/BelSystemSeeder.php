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

        $park = Department::query()->updateOrCreate(
            ['name' => 'Park ve Bahçeler Müdürlüğü'],
            [
                'manager_name' => 'Elif Demir',
                'manager_phone' => '0288 123 45 20',
                'foreman_name' => 'Mehmet Demir',
                'foreman_phone' => '0288 123 45 21',
                'staff_count' => 65,
            ],
        );

        $veteriner = Department::query()->updateOrCreate(
            ['name' => 'Veteriner İşleri Müdürlüğü'],
            [
                'manager_name' => 'Burcu Aydın',
                'manager_phone' => '0288 123 45 30',
                'foreman_name' => 'Burak Yılmaz',
                'foreman_phone' => '0288 123 45 31',
                'staff_count' => 18,
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
        $park->update(['vice_mayor_id' => $viceMayor->id]);
        $veteriner->update(['vice_mayor_id' => $viceMayor->id]);

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

        $fenForeman = User::query()->updateOrCreate(
            ['email' => 'saha@kirklareli.bel.tr'],
            [
                'name' => 'Fen İşleri Saha Şefi',
                'password' => Hash::make('password'),
                'role' => UserRole::Staff,
                'department_id' => $fen->id,
                'email_verified_at' => now(),
            ],
        );

        $temizlikForeman = User::query()->updateOrCreate(
            ['email' => 'saha.temizlik@kirklareli.bel.tr'],
            [
                'name' => 'Temizlik Saha Şefi',
                'password' => Hash::make('password'),
                'role' => UserRole::Staff,
                'department_id' => $temizlik->id,
                'email_verified_at' => now(),
            ],
        );

        $fen->update(['foreman_user_id' => $fenForeman->id]);
        $temizlik->update(['foreman_user_id' => $temizlikForeman->id]);
    }
}
