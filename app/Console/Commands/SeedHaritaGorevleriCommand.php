<?php

namespace App\Console\Commands;

use App\Services\SeedDepartmentMapTasks;
use Illuminate\Console\Command;

class SeedHaritaGorevleriCommand extends Command
{
    protected $signature = 'gorev:seed-harita-gorevleri
                            {--only-missing : Yalnızca henüz KRL-HARITA-* kodu olmayan müdürlükler için oluştur}';

    protected $description = 'Her müdürlük için konumlu (enlem/boylam) açık görev oluşturur — Saha Haritası\'nda görünür';

    public function handle(SeedDepartmentMapTasks $seeder): int
    {
        $stats = $seeder->seed($this->option('only-missing'));

        if ($stats['total'] === 0) {
            $this->warn('Veritabanında müdürlük bulunamadı. Önce gorev:import-kullanicilar veya BelSystemSeeder çalıştırın.');

            return self::FAILURE;
        }

        $this->info("Harita görevleri: {$stats['created']} yeni, {$stats['updated']} güncellendi, {$stats['skipped']} atlandı (toplam {$stats['total']} müdürlük).");
        $this->line('Saha Haritası: /admin/saha-haritasi — açık görevler pin olarak listelenir.');

        return self::SUCCESS;
    }
}
