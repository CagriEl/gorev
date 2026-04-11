<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Directorate;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FaaliyetSetiSeeder extends Seeder
{
    private const CSV_FILENAME = 'faaliyet_seti.csv';

    /**
     * referans_tasarim.html içindeki kısa birim adı → CSV’deki resmi müdürlük adı.
     * (CSV’de ayrı satır olmayanlar en yakın hizmet müdürlüğüne yönlendirilir.)
     */
    private const REFERENCE_UNIT_TO_DIRECTORATE = [
        'Fen İşleri' => 'Fen İşleri Müdürlüğü',
        'Zabıta' => 'Zabıta Müdürlüğü',
        'Park ve Bahçeler' => 'Fen İşleri Müdürlüğü',
        'Temizlik İşleri' => 'Temizlik İşleri Müdürlüğü',
        'Çevre Koruma' => 'İklim Değişikliği ve Sıfır Atık Müdürlüğü',
        'Veteriner İşleri' => 'Veteriner İşleri Müdürlüğü',
        'Kültür ve Sosyal' => 'Kültür, Sanat ve Sosyal İşler Müdürlüğü',
        'Ruhsat ve Denetim' => 'İmar ve Şehircilik Müdürlüğü',
    ];

    public function run(): void
    {
        $path = base_path(self::CSV_FILENAME);
        if (! File::isFile($path)) {
            $this->command?->error('CSV bulunamadı: '.$path);

            return;
        }

        $this->seedFromCsv($path);
        $this->seedReferenceTasks();
    }

    private function seedFromCsv(string $path): void
    {
        $raw = File::get($path);
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $headerIndex = null;
        foreach ($lines as $i => $line) {
            $row = str_getcsv($line);
            if (($row[0] ?? '') === 'Müdürlük') {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            $this->command?->error('CSV başlık satırı (Müdürlük) bulunamadı.');

            return;
        }

        $directorateOrder = 0;
        $directorateIds = [];

        for ($i = $headerIndex + 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (count($row) < 8) {
                continue;
            }

            [$müdürlük, $kod, $aile, $kategori, $kapsam, $ölçü, $kpi, $kaynak] = array_map(
                fn (?string $v) => $v !== null ? trim($v) : '',
                [
                    $row[0] ?? '',
                    $row[1] ?? '',
                    $row[2] ?? '',
                    $row[3] ?? '',
                    $row[4] ?? '',
                    $row[5] ?? '',
                    $row[6] ?? '',
                    $row[7] ?? '',
                ]
            );

            if ($müdürlük === '' || $kod === '') {
                continue;
            }

            if (! isset($directorateIds[$müdürlük])) {
                $directorateOrder++;
                $directorate = Directorate::updateOrCreate(
                    ['name' => $müdürlük],
                    ['sort_order' => $directorateOrder]
                );
                $directorateIds[$müdürlük] = $directorate->id;
            }

            Activity::updateOrCreate(
                ['code' => $kod],
                [
                    'directorate_id' => $directorateIds[$müdürlük],
                    'family_name' => $aile,
                    'category' => $kategori,
                    'scope' => $kapsam !== '' ? $kapsam : null,
                    'measure_unit' => $ölçü !== '' ? $ölçü : null,
                    'kpi_sla' => $kpi !== '' ? $kpi : null,
                    'data_source' => $kaynak !== '' ? $kaynak : null,
                ]
            );
        }

        $this->command?->info('Faaliyet seti: '.count($directorateIds).' müdürlük, faaliyetler güncellendi.');
    }

    private function seedReferenceTasks(): void
    {
        $base = Carbon::create(2026, 4, 1, 0, 0, 0, config('app.timezone'));

        $rows = [
            ['#KRL-1051', 'Ana Şebeke Su Patlağı', 'Fen İşleri', 'Ahmet Yılmaz', 'Cumhuriyet Meydanı', TaskStatus::Sahada, TaskPriority::Kritik, 41.7340, 27.2225, 2, '08:15', '08:25', null, null],
            ['#KRL-1050', 'Ağaç Devrilmesi', 'Park ve Bahçeler', 'Mehmet Demir', 'Yayla Mahallesi', TaskStatus::Yonlendirildi, TaskPriority::Normal, 41.7375, 27.2280, 1, '09:10', null, null, null],
            ['#KRL-1049', 'Kaldırım İşgali', 'Zabıta', 'Ayşe Kaya', 'İstasyon Caddesi', TaskStatus::Tamamlandi, TaskPriority::Yuksek, 41.7310, 27.2260, 0, '08:30', '08:35', '08:45', 15],
            ['#KRL-1048', 'Yaralı Hayvan İhbarı', 'Veteriner İşleri', 'Burak Yılmaz', 'Karacaibrahim', TaskStatus::Sahada, TaskPriority::Yuksek, 41.7405, 27.2155, 0, '10:05', '10:15', null, null],
            ['#KRL-1047', 'Konteyner Hasarı', 'Temizlik İşleri', 'Caner Erol', 'İstiklal Cad.', TaskStatus::Tamamlandi, TaskPriority::Normal, 41.7350, 27.2200, 0, '07:20', '07:30', '08:25', 65],
            ['#KRL-1045', 'Dere Kirliliği', 'Çevre Koruma', 'Oğuzhan Çelik', 'Karahıdır', TaskStatus::Yonlendirildi, TaskPriority::Normal, 41.7250, 27.2400, 0, '11:00', null, null, null],
            ['#KRL-1044', 'Festival Sahne Kurulumu', 'Kültür ve Sosyal', 'Emre Can', 'Festival Alanı', TaskStatus::Tamamlandi, TaskPriority::Normal, 41.7380, 27.2100, 0, '09:00', '09:30', '13:00', 240],
            ['#KRL-1043', 'Pazar Yeri Denetimi', 'Ruhsat ve Denetim', 'Fatma Gül', 'Kapalı Pazar Yeri', TaskStatus::Tamamlandi, TaskPriority::Normal, 41.7300, 27.2150, 0, '08:00', '08:15', '09:30', 90],
        ];

        foreach ($rows as $r) {
            [
                $ref, $title, $unit, $assignee, $loc, $status, $priority,
                $lat, $lng, $photos, $assignT, $dispatchT, $resolveT, $resolveMin,
            ] = $r;

            $directorateName = self::REFERENCE_UNIT_TO_DIRECTORATE[$unit] ?? null;
            if ($directorateName === null) {
                continue;
            }

            $directorateId = Directorate::where('name', $directorateName)->value('id');
            if ($directorateId === null) {
                $this->command?->warn("Görev atlanıyor (müdürlük yok): {$ref} → {$directorateName}");

                continue;
            }

            Task::updateOrCreate(
                ['reference_code' => $ref],
                [
                    'title' => $title,
                    'location_description' => $loc,
                    'directorate_id' => $directorateId,
                    'activity_id' => null,
                    'assignee_name' => $assignee,
                    'status' => $status,
                    'priority' => $priority,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'assigned_at' => $this->demoTime($base, $assignT),
                    'dispatched_at' => $this->demoTime($base, $dispatchT),
                    'resolved_at' => $this->demoTime($base, $resolveT),
                    'resolve_duration_minutes' => $resolveMin,
                    'photo_count' => $photos,
                ]
            );
        }

        $this->command?->info('Referans görevleri: '.count($rows).' kayıt.');
    }

    private function demoTime(Carbon $base, ?string $time): ?Carbon
    {
        if ($time === null || $time === '' || $time === 'Bekleniyor') {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            return null;
        }

        return (clone $base)->setTime((int) $m[1], (int) $m[2], 0);
    }
}
