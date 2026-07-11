<?php

namespace App\Console\Commands;

use App\Services\ClassifierModelTrainer;
use Illuminate\Console\Command;

class RetrainClassifierCommand extends Command
{
    protected $signature = 'classifier:retrain';

    protected $description = 'Panel örnekleri ve görev verisiyle müdürlük sınıflandırıcı modelini yeniden eğitir';

    public function handle(ClassifierModelTrainer $trainer): int
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $this->info('Eğitim başlıyor (birkaç dakika sürebilir)...');

        $result = $trainer->retrain();

        if (! $result['success']) {
            $this->error('Eğitim başarısız.');
            $this->line($result['output']);

            return self::FAILURE;
        }

        $this->info('Eğitim tamamlandı.');
        $this->line('Son eğitim: '.($trainer->lastTrainedAt() ?? '—'));

        return self::SUCCESS;
    }
}
