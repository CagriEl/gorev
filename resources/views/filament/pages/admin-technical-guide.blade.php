<x-filament-panels::page>
    @php
        $markdown = file_get_contents(base_path('docs/yonetici-teknik-rehber.md')) ?: '# Yönetici/Teknik Rehber bulunamadı';
    @endphp

    <div class="prose max-w-none dark:prose-invert">
        {!! \Illuminate\Support\Str::markdown($markdown) !!}
    </div>
</x-filament-panels::page>
