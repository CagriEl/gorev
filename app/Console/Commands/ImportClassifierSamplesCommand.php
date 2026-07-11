<?php

namespace App\Console\Commands;

use App\Models\ClassifierTrainingSample;
use App\Models\Department;
use App\Services\MudurlukClassifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportClassifierSamplesCommand extends Command
{
    protected $signature = 'classifier:import-samples
                            {--file= : CSV yolu (varsayılan: ml/.../belediye_sikayetleri.csv)}
                            {--dry-run : Kayıt eklemeden özet göster}';

    protected $description = 'Belediye şikâyet CSV dosyasını eğitim örnekleri tablosuna aktarır';

    public function handle(MudurlukClassifierService $slugger): int
    {
        $path = $this->option('file')
            ?: base_path('ml/mudurluk-siniflandirici/data/belediye_sikayetleri.csv');

        if (! File::exists($path)) {
            $this->error("Dosya bulunamadı: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);
        if ($rows === []) {
            $this->warn('CSV boş veya okunamadı.');

            return self::FAILURE;
        }

        $slugToId = $this->resolveSlugToDepartmentIds($slugger);

        $imported = 0;
        $skipped = 0;
        $unknown = 0;

        foreach ($rows as $row) {
            $text = trim($row['sikayet_metni']);
            $slug = strtoupper(trim($row['mudurluk_slug']));

            if ($text === '' || $slug === '') {
                $skipped++;

                continue;
            }

            $departmentId = $slugToId[$slug] ?? null;
            if ($departmentId === null) {
                $this->warn("Bilinmeyen müdürlük slug: {$slug} — {$text}");
                $unknown++;

                continue;
            }

            $exists = ClassifierTrainingSample::query()
                ->where('sikayet_metni', $text)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $imported++;

                continue;
            }

            ClassifierTrainingSample::query()->create([
                'sikayet_metni' => $text,
                'department_id' => $departmentId,
                'mudurluk_slug' => $slug,
                'is_active' => true,
                'notes' => 'belediye_sikayetleri.csv',
            ]);
            $imported++;
        }

        $this->info("Dosya: {$path}");
        $this->info("Toplam satır: ".count($rows));
        $this->info($this->option('dry-run') ? "Eklenecek: {$imported}" : "Eklenen: {$imported}");
        $this->info("Atlanan (mükerrer/boş): {$skipped}");
        if ($unknown > 0) {
            $this->warn("Eşleşmeyen slug: {$unknown}");
        }

        if (! $this->option('dry-run') && $imported > 0) {
            $this->line('');
            $this->line('Modeli güncellemek için: php artisan classifier:retrain');
            $this->line('(veya panel → Yapay Zeka → Modeli yeniden eğit)');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{sikayet_metni: string, mudurluk_slug: string}>
     */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $textIdx = array_search('sikayet_metni', $header, true);
        $slugIdx = array_search('mudurluk_slug', $header, true);
        if ($textIdx === false || $slugIdx === false) {
            fclose($handle);

            return [];
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = [
                'sikayet_metni' => (string) ($data[$textIdx] ?? ''),
                'mudurluk_slug' => (string) ($data[$slugIdx] ?? ''),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    protected function resolveSlugToDepartmentIds(MudurlukClassifierService $slugger): array
    {
        $slugToId = [];
        $labelsFile = base_path('ml/mudurluk-siniflandirici/labels.json');

        if (File::exists($labelsFile)) {
            $payload = json_decode(File::get($labelsFile), true);
            foreach ($payload['labels'] ?? [] as $row) {
                $slug = (string) ($row['slug'] ?? '');
                $name = (string) ($row['name'] ?? '');
                if ($slug === '' || $name === '') {
                    continue;
                }
                $department = Department::query()->where('name', $name)->first();
                if ($department) {
                    $slugToId[$slug] = $department->id;
                }
            }
        }

        foreach (Department::query()->get(['id', 'name']) as $department) {
            $slug = $slugger->slugify($department->name);
            $slugToId[$slug] = $department->id;
        }

        return $slugToId;
    }
}
