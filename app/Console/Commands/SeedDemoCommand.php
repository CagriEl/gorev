<?php

namespace App\Console\Commands;

use App\Services\SeedFullDemoData;
use Illuminate\Console\Command;

class SeedDemoCommand extends Command
{
    protected $signature = 'gorev:seed-demo
                            {--skip-import : Mevcut müdürlük/kullanıcıları koru (yalnızca görev ve demo verisi ekle)}
                            {--force : Onay sormadan çalıştır}';

    protected $description = 'Tüm alanlar için kapsamlı demo verisi oluşturur (müdürlükler, görevler, onaylar, sınıflandırıcı, push token)';

    public function handle(SeedFullDemoData $seeder): int
    {
        $skipImport = $this->option('skip-import');

        if (! $this->option('force') && ! $skipImport && ! $this->confirm(
            'Mevcut müdürlükler, kullanıcılar (admin hariç) ve görevler silinip CSV\'den yeniden yüklenecek. Devam?',
            true,
        )) {
            return self::SUCCESS;
        }

        $stats = $seeder->seed(freshImport: ! $skipImport);

        if ($stats['import'] !== null) {
            $this->info('Kullanıcı / müdürlük CSV içe aktarıldı.');
            $this->table(
                ['Metrik', 'Adet'],
                [
                    ['Silinen kullanıcı', $stats['import']['removed_users']],
                    ['Müdürlük', $stats['import']['departments']],
                    ['Başkan yardımcısı', $stats['import']['vice_mayors']],
                    ['Birim yöneticisi', $stats['import']['managers']],
                    ['Saha şefi', $stats['import']['foremen']],
                ],
            );
        }

        $this->newLine();
        $this->info('Demo verisi hazır.');
        $this->table(
            ['Alan', 'Detay'],
            [
                ['Demo fotoğraflar', implode(', ', $stats['photos'])],
                ['Harita görevleri', "{$stats['map_tasks']['created']} yeni, {$stats['map_tasks']['updated']} güncellendi ({$stats['map_tasks']['total']} müdürlük)"],
                ['İş akışı görevleri', (string) $stats['workflow_tasks']],
                ['Onay talepleri', "bekleyen {$stats['approvals']['pending']}, onaylı {$stats['approvals']['approved']}, reddedilen {$stats['approvals']['rejected']}"],
                ['Sınıflandırıcı örnekleri', (string) $stats['classifier_samples']],
                ['Push token', (string) $stats['push_tokens']],
            ],
        );

        $this->newLine();
        $this->line('Panel: /admin — admin@admin.com / password');
        $this->line('Tüm kullanıcı şifreleri: password');
        $this->line('Saha Haritası: /admin/saha-haritasi');
        $this->line('Başkan yardımcısı raporu: /admin/baskan-yardimcisi-raporu');
        $this->line('Sınıflandırıcı laboratuvarı: /admin/mudurluk-siniflandirici');

        if (! $this->option('skip-import')) {
            $this->newLine();
            $this->comment('storage:link henüz yoksa: php artisan storage:link');
        }

        return self::SUCCESS;
    }
}
