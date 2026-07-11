<?php

namespace App\Console\Commands;

use App\Services\ImportMunicipalityUsersFromCsv;
use App\Services\ResetPresentationData;
use Illuminate\Console\Command;

class SunumSifirlaCommand extends Command
{
    protected $signature = 'gorev:sunum-sifirla
                            {--force : Onay sormadan sıfırla}
                            {--keep-training : Eğitim örneklerini koru}
                            {--keep-audit : Denetim kayıtlarını koru}
                            {--reimport : Ardından kullanıcı/müdürlük CSV içe aktarımını çalıştır}
                            {--keep-demo-org : Sunum Demo müdürlüğü ve kullanıcılarını koru}';

    protected $description = 'Sunum öncesi demo görevleri ve operasyonel veriyi temizler (müdürlük/kullanıcı yapısı kalır)';

    public function handle(
        ResetPresentationData $reset,
        ImportMunicipalityUsersFromCsv $importer,
    ): int {
        if (! $this->option('force') && ! $this->confirm(
            'Tüm görevler, onay talepleri ve (varsayılan) eğitim örnekleri/denetim kayıtları silinecek. Müdürlük ve kullanıcılar korunur. Devam?',
            false,
        )) {
            return self::SUCCESS;
        }

        $stats = $reset->reset(
            clearTrainingSamples: ! $this->option('keep-training'),
            clearActivityLog: ! $this->option('keep-audit'),
            clearDemoOrganisation: ! $this->option('keep-demo-org'),
        );

        $this->info('Sunum verisi sıfırlandı.');
        $this->table(
            ['Silinen', 'Adet'],
            [
                ['Görev', $stats['tasks']],
                ['Onay talebi', $stats['approvals']],
                ['Eğitim örneği', $stats['training_samples']],
                ['Denetim kaydı', $stats['activity_logs']],
                ['API token', $stats['api_tokens']],
                ['Sunum demo müdürlük', $stats['demo_department']],
                ['Sunum demo kullanıcı', $stats['demo_users']],
            ],
        );

        if ($stats['upload_dirs_cleared'] !== []) {
            $this->line('Temizlenen yükleme klasörleri: '.implode(', ', $stats['upload_dirs_cleared']));
        }

        if ($this->option('reimport')) {
            $file = base_path('database/seeders/data/kullanicilar-2026-05-31.csv');
            if (! is_readable($file)) {
                $this->error("CSV bulunamadı: {$file}");

                return self::FAILURE;
            }

            $importStats = $importer->import($file, 'admin@admin.com');
            $this->newLine();
            $this->info('Kullanıcı/müdürlük CSV yeniden içe aktarıldı.');
            $this->table(
                ['Metrik', 'Adet'],
                [
                    ['Müdürlük', $importStats['departments']],
                    ['Başkan yardımcısı', $importStats['vice_mayors']],
                    ['Birim yöneticisi', $importStats['managers']],
                    ['Saha şefi', $importStats['foremen']],
                ],
            );
        }

        $this->newLine();
        $this->line('Panel: http://gorev.test/admin — admin@admin.com / password');
        $this->line('Sunum akışı: docs/sunum-rehberi.md');

        return self::SUCCESS;
    }
}
