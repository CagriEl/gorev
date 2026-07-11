<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Tahmin dene</x-slot>
            <x-slot name="description">
                Metni girin; model hangi müdürlüğe gitmesi gerektiğini tahmin eder.
            </x-slot>

            <form wire:submit="runPrediction" class="space-y-4">
                <textarea
                    wire:model="testText"
                    rows="4"
                    class="fi-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    placeholder="Örn: kaldırım taşları çöktü, yürümek zor"
                ></textarea>
                @error('testText')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror

                <x-filament::button type="submit" icon="heroicon-o-sparkles">
                    Tahmin et
                </x-filament::button>
            </form>

            @if ($prediction)
                <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Müdürlük</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">
                                {{ $prediction['department_name'] ?? $prediction['department_slug'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Slug</dt>
                            <dd class="font-mono text-xs">{{ $prediction['department_slug'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Güven</dt>
                            <dd>{{ number_format(($prediction['confidence'] ?? 0) * 100, 1) }}%</dd>
                        </div>
                        @if (! empty($prediction['classification_method']))
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 dark:text-gray-400">Yöntem</dt>
                                <dd>
                                    @if (($prediction['classification_method'] ?? '') === 'keyword')
                                        Anahtar kelime (yüksek eşleşme)
                                    @else
                                        Yapay zeka modeli
                                    @endif
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">İnceleme gerekli mi?</dt>
                            <dd>
                                @if ($prediction['needs_review'] ?? true)
                                    <span class="text-warning-600">Evet</span>
                                @else
                                    <span class="text-success-600">Hayır</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if (! empty($prediction['top_predictions']))
                        <p class="mt-4 text-xs font-medium text-gray-500 dark:text-gray-400">Alternatifler</p>
                        <ul class="mt-1 space-y-1 text-xs">
                            @foreach ($prediction['top_predictions'] as $alt)
                                <li>
                                    {{ $alt['department_slug'] ?? '—' }}
                                    — {{ number_format(($alt['confidence'] ?? 0) * 100, 1) }}%
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-4">
                        <x-filament::button
                            wire:click="addPredictionAsSample"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-plus"
                        >
                            Bu metni eğitim örneği olarak kaydet
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Durum</x-slot>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">ML servisi</dt>
                    <dd class="font-medium">
                        @if ($serviceHealth['ready'] ?? false)
                            <span class="text-success-600">Hazır</span>
                        @else
                            <span class="text-danger-600">Kapalı — {{ $serviceHealth['message'] ?? 'Bağlantı yok' }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Aktif eğitim örneği</dt>
                    <dd class="font-medium">{{ $activeSampleCount }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Son model eğitimi</dt>
                    <dd class="font-medium">{{ $lastTrainedAt ?? 'Henüz yok' }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                Yeni örnekler eklemek için
                <a
                    href="{{ \App\Filament\Resources\ClassifierTrainingSampleResource::getUrl('index') }}"
                    class="text-primary-600 underline dark:text-primary-400"
                >
                    Model eğitim örnekleri
                </a>
                menüsünü kullanın. Yeterli örnekten sonra (yönetici) «Modeli yeniden eğit» ile BERTurk güncellenir.
            </p>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                API: {{ config('services.mudurluk_classifier.url') }}
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
