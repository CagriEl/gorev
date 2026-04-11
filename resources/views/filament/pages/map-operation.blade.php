<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    @endpush

    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Tamamlanmamış görevler haritada durumlarına göre renklendirilmiştir. Pinlere tıklayarak detayları görebilirsiniz.
        </p>

        <div
            wire:ignore
            id="bel-map-operation"
            class="z-0 h-[70vh] w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-900"
        ></div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <style>
            .status-pin {
                width: 14px;
                height: 14px;
                border-radius: 9999px;
                border: 2px solid #fff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.35);
            }

            .status-pin--bekliyor {
                background: #9ca3af;
            }

            .status-pin--yonlendirildi {
                background: #3b82f6;
            }

            .status-pin--sahada {
                background: #f59e0b;
            }
        </style>
        <script>
            function belInitMapOperation() {
                const el = document.getElementById('bel-map-operation');
                if (!el || typeof L === 'undefined') {
                    return;
                }

                if (el._belLeafletMap) {
                    el._belLeafletMap.remove();
                    el._belLeafletMap = null;
                }

                const tasks = @js($mapTasks);
                const center = tasks.length
                    ? [tasks[0].lat, tasks[0].lng]
                    : [41.7351, 27.2252];

                const map = L.map(el).setView(center, 13);
                el._belLeafletMap = map;

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                const iconFor = (status) => {
                    const cls =
                        {
                            bekliyor: 'status-pin status-pin--bekliyor',
                            yonlendirildi: 'status-pin status-pin--yonlendirildi',
                            sahada: 'status-pin status-pin--sahada',
                        }[status] || 'status-pin status-pin--bekliyor';

                    return L.divIcon({
                        className: '',
                        html: `<span class="${cls}" title=""></span>`,
                        iconSize: [18, 18],
                        iconAnchor: [9, 9],
                    });
                };

                tasks.forEach((t) => {
                    const marker = L.marker([t.lat, t.lng], { icon: iconFor(t.status) }).addTo(map);
                    marker.bindPopup(
                        `<strong>${t.title}</strong><br/><span style="font-size:12px;opacity:.85">${t.statusLabel}</span>`,
                    );
                });

                if (tasks.length > 1) {
                    const bounds = L.latLngBounds(tasks.map((t) => [t.lat, t.lng]));
                    map.fitBounds(bounds.pad(0.15));
                }

                setTimeout(() => map.invalidateSize(), 300);
            }

            document.addEventListener('livewire:navigated', belInitMapOperation);
            document.addEventListener('DOMContentLoaded', belInitMapOperation);
        </script>
    @endpush
</x-filament-panels::page>
