@php
    use App\Enums\TaskStatus;

    /** @var \Filament\Infolists\Components\Entry $entry */
    $task = $entry->getRecord();
    $activities = $task
        ? \Spatie\Activitylog\Models\Activity::query()
            ->forSubject($task)
            ->where('log_name', 'task')
            ->orderByDesc('created_at')
            ->get()
        : collect();

    $statusLabel = static function (?string $raw): ?string {
        if ($raw === null) {
            return null;
        }
        $enum = TaskStatus::tryFrom($raw);

        return $enum?->getLabel() ?? $raw;
    };
@endphp

<div class="space-y-4">
    @forelse ($activities as $activity)
        @php
            $props = $activity->properties->get('attributes', []);
            $old = $activity->properties->get('old', []);
            $statusNew = $props['status'] ?? null;
            $statusOld = $old['status'] ?? null;
        @endphp
        <div class="relative border-s-2 border-gray-200 ps-4 dark:border-white/10">
            <span
                class="absolute -start-[5px] top-1.5 size-2 rounded-full bg-primary-500 ring-4 ring-white dark:ring-gray-900"
            ></span>
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                Durum güncellendi
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $activity->created_at?->translatedFormat('d.m.Y H:i') }}
            </p>
            @if ($statusOld !== null || $statusNew !== null)
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                    @if ($statusOld !== null && $statusNew !== null)
                        <span class="text-xs">{{ $statusLabel($statusOld) }}</span>
                        →
                        <span class="text-xs">{{ $statusLabel($statusNew) }}</span>
                    @elseif ($statusNew !== null)
                        <span class="text-xs">{{ $statusLabel($statusNew) }}</span>
                    @endif
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Henüz kayıtlı durum değişikliği yok.</p>
    @endforelse
</div>
