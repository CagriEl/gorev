<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CSV dosyasını okuyup Directorates ve Activities tablolarını doldurur
$file = fopen(database_path('seeders/faaliyet_seti.csv'), 'r');
// ... döngü ile verileri Insert et ...

// HTML'deki örnek görevleri ekle
Task::create([
    'title' => 'Ana Şebeke Su Patlağı',
    'directorate_id' => 1, // Fen İşleri
    'status' => 'on-site',
    'priority' => 'critical',
    // ... diğer alanlar ...
]);
    }
}
