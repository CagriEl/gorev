<?php

namespace App\Console\Commands;

use App\Services\ImportMunicipalityUsersFromCsv;
use Illuminate\Console\Command;

class ImportKullanicilarCommand extends Command
{
    protected $signature = 'gorev:import-kullanicilar
                            {file? : CSV dosya yolu}
                            {--admin-email=admin@admin.com : Korunacak yönetici e-postası}
                            {--force : Onay sormadan içe aktar}';

    protected $description = 'Müdürlük, başkan yardımcısı ve saha şefi verilerini CSV\'den içe aktarır (mevcut kullanıcı/müdürlük verisini temizler, admin kalır)';

    public function handle(ImportMunicipalityUsersFromCsv $importer): int
    {
        $file = $this->argument('file')
            ?: base_path('database/seeders/data/kullanicilar-2026-05-31.csv');

        if (! is_readable($file)) {
            $this->error("CSV bulunamadı: {$file}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Mevcut müdürlükler, kullanıcılar (admin hariç) ve görevler silinecek. Devam?', true)) {
            return self::SUCCESS;
        }

        $stats = $importer->import($file, $this->option('admin-email'));

        $this->table(
            ['Metrik', 'Adet'],
            [
                ['Silinen kullanıcı', $stats['removed_users']],
                ['Müdürlük', $stats['departments']],
                ['Başkan yardımcısı', $stats['vice_mayors']],
                ['Birim yöneticisi', $stats['managers']],
                ['Saha şefi', $stats['foremen']],
            ],
        );

        $this->info('Varsayılan şifre: password');
        $this->line('Başkan yardımcısı–müdürlük eşlemesi: eski directorate_id sırasına göre ilk yarı / ikinci yarı.');

        return self::SUCCESS;
    }
}
