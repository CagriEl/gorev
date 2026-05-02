@php
    $lat = (float) ($lat ?? 41.7351);
    $lng = (float) ($lng ?? 27.2252);
    $livewireId = $field->getLivewire()->getId();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-data="leafletTaskLocation({
            livewireId: @js($livewireId),
            initialLat: {{ number_format($lat, 7, '.', '') }},
            initialLng: {{ number_format($lng, 7, '.', '') }},
        })"
        x-init="init()"
        class="w-full space-y-3"
    >
        <div class="flex flex-wrap items-center gap-2">
            <x-filament::button
                type="button"
                color="gray"
                size="lg"
                class="!min-h-[3.5rem] !px-6 !py-4 !text-base"
                x-on:click="locateMe()"
            >
                Şu anki konumumu getir
            </x-filament::button>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Mobil cihazlarda konum izni gerekir; üretimde HTTPS kullanın.
            </span>
        </div>

        <div
            x-ref="mapEl"
            class="z-0 h-80 w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-900"
        ></div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            İsteğe bağlı: haritaya tıklayın veya işareti sürükleyerek görev noktasını işaretleyin; yol tarifi ve saha doğrulaması için kullanılır. Ayrıca «Şu anki konumumu getir» ile bulunduğunuz yeri işaretleyebilirsiniz.
        </p>
    </div>
</x-dynamic-component>
