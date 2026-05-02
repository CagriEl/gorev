{{--
    Saha Haritası — Filament teması (Tailwind). Bootstrap kullanılmaz (sidebar / panel stillerini bozuyordu).
    Leaflet: panel hook (HEAD_END + SCRIPTS_BEFORE).
--}}

<x-filament-panels::page>
    @push('styles')
        <style>
            /* Yalnızca bu modül; panel genel CSS'ine dokunmaz */
            #saha-haritasi .saha-map-box {
                height: 500px;
                width: 100%;
                border-radius: 0.75rem;
                z-index: 1;
                position: relative;
            }
            #saha-haritasi .leaflet-container {
                border-radius: 0.75rem;
                font-family: inherit;
            }
            .saha-pin {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                border: 3px solid #fff;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
                cursor: pointer;
                transition: transform 0.15s;
            }
            .saha-pin:hover { transform: scale(1.3); }
            .saha-pin-acil { background: #dc2626; }
            .saha-pin-bekliyor { background: #ca8a04; }
            .saha-pin-islemde { background: #2563eb; }
            .saha-table-scroll {
                max-height: 28rem;
                overflow-y: auto;
            }
            .saha-table-scroll thead th {
                position: sticky;
                top: 0;
                z-index: 2;
            }
        </style>
    @endpush

    <div wire:ignore id="saha-haritasi" class="space-y-6">
            {{-- Harita kartı (Filament görünümü) --}}
            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
            >
                <div class="relative border-b border-gray-200 p-4 dark:border-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-3 pe-36">
                        <div>
                            <h2 class="text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                                Saha Haritası — Kırklareli
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Haritada yalnızca <strong class="font-medium text-gray-700 dark:text-gray-300">açık</strong>
                                ve koordinatı tanımlı görevler işaretlenir.
                            </p>
                        </div>
                    </div>
                    <span
                        class="absolute end-4 top-4 inline-flex items-center gap-x-1.5 rounded-md px-3 py-1.5 text-xs font-medium ring-1 ring-inset ring-success-600/20 bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30"
                    >
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"
                            ></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-success-500"></span>
                        </span>
                        Aktif görevler
                    </span>
                </div>
                <div class="p-4 pt-0">
                    <div id="mapContainer" class="saha-map-box bg-gray-100 dark:bg-gray-950"></div>
                    <p
                        id="sahaMapError"
                        class="mt-2 hidden text-sm text-danger-600 dark:text-danger-400"
                        role="alert"
                    >
                        Harita kütüphanesi yüklenemedi. Sayfayı yenileyin.
                    </p>
                </div>
            </div>

            {{-- Görev tablosu --}}
            <div
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
            >
                <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Görev listesi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tüm görevler (kapsamınıza göre). Tamamlananlar haritada yer almaz.
                    </p>
                </div>
                <div class="saha-table-scroll">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/10" id="sahaTaskTable">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400"
                                    >
                                        ID
                                    </th>
                                    <th
                                        class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400"
                                    >
                                        Görev başlığı
                                    </th>
                                    <th
                                        class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400"
                                    >
                                        Sorumlu
                                    </th>
                                    <th
                                        class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400"
                                    >
                                        Durum
                                    </th>
                                    <th
                                        class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400"
                                    >
                                        Adres
                                    </th>
                                    <th
                                        class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400"
                                    >
                                        İşlem
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                id="sahaTaskTableBody"
                                class="divide-y divide-gray-200 dark:divide-white/10"
                            ></tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>
</x-filament-panels::page>

@script
<script>
(function () {
    const TASKS = @js($mapTasks);

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function safeHref(u) {
        return String(u || '#').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function hasCoords(t) {
        return t.lat != null && t.lng != null && Number.isFinite(+t.lat) && Number.isFinite(+t.lng);
    }

    function detailUrl(t) {
        return t.url || '/admin/tasks/' + encodeURIComponent(String(t.id));
    }

    function pinClass(task) {
        if (task.priority === 'kritik') return 'saha-pin-acil';
        if (task.status === 'bekliyor') return 'saha-pin-bekliyor';
        return 'saha-pin-islemde';
    }

    function statusBadgeHtml(task) {
        var cls =
            {
                bekliyor: 'bg-warning-400/15 text-warning-700 ring-warning-600/25 dark:text-warning-400 dark:ring-warning-400/30',
                yonlendirildi:
                    'bg-info-400/15 text-info-700 ring-info-600/25 dark:text-info-400 dark:ring-info-400/30',
                sahada: 'bg-primary-400/15 text-primary-700 ring-primary-600/25 dark:text-primary-400 dark:ring-primary-400/30',
                cozuldu: 'bg-primary-400/15 text-primary-700 ring-primary-600/25 dark:text-primary-400 dark:ring-primary-400/30',
                onay_bekliyor:
                    'bg-warning-400/15 text-warning-700 ring-warning-600/25 dark:text-warning-400 dark:ring-warning-400/30',
                kapatildi:
                    'bg-success-400/15 text-success-700 ring-success-600/25 dark:text-success-400 dark:ring-success-400/30',
                tamamlandi:
                    'bg-success-400/15 text-success-700 ring-success-600/25 dark:text-success-400 dark:ring-success-400/30',
            }[task.status] || 'bg-gray-400/15 text-gray-700 ring-gray-600/25 dark:text-gray-400';
        return (
            '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' +
            cls +
            '">' +
            esc(task.statusLabel || '') +
            '</span>'
        );
    }

    function flytoButton(task) {
        if (task.isOpen && hasCoords(task)) {
            return (
                '<button type="button" class="saha-flyto inline-flex items-center gap-x-1 rounded-lg bg-primary-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:bg-primary-500 dark:hover:bg-primary-400" data-id="' +
                esc(String(task.id)) +
                '">Haritada gör</button>'
            );
        }
        if (!task.isOpen) {
            return '<span class="text-xs text-gray-400 dark:text-gray-500">—</span>';
        }
        return '<span class="text-xs text-gray-400 dark:text-gray-500">Konum yok</span>';
    }

    function initSahaMap() {
        var errEl = document.getElementById('sahaMapError');
        var el = document.getElementById('mapContainer');
        if (!el) return;
        if (typeof L === 'undefined') {
            if (errEl) errEl.classList.remove('hidden');
            return;
        }
        if (errEl) errEl.classList.add('hidden');

        if (el._sahaMap) {
            try {
                el._sahaMap.remove();
            } catch (e) {}
            el._sahaMap = null;
        }

        var map = L.map('mapContainer').setView([41.7333, 27.2167], 14);
        el._sahaMap = map;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        var tbody = document.getElementById('sahaTaskTableBody');
        if (tbody) tbody.replaceChildren();

        var markers = {};
        var pts = [];

        if (tbody && TASKS.length === 0) {
            var emptyTr = document.createElement('tr');
            emptyTr.innerHTML =
                '<td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">' +
                'Görev bulunamadı. Kapsamınıza göre kayıtlı görev yok veya henüz eklenmemiş.' +
                '</td>';
            tbody.appendChild(emptyTr);
        }

        TASKS.forEach(function (task) {
            if (tbody) {
                var tr = document.createElement('tr');
                tr.className =
                    'hover:bg-gray-50 dark:hover:bg-white/5 transition-colors';
                tr.innerHTML =
                    '<td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">' +
                    esc(String(task.id)) +
                    '</td>' +
                    '<td class="max-w-xs px-4 py-3 text-sm text-gray-950 dark:text-white">' +
                    esc(task.title || '') +
                    '</td>' +
                    '<td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">' +
                    esc(task.assignee_name || '—') +
                    '</td>' +
                    '<td class="whitespace-nowrap px-4 py-3">' +
                    statusBadgeHtml(task) +
                    '</td>' +
                    '<td class="max-w-xs px-4 py-3 text-sm text-gray-600 dark:text-gray-400">' +
                    esc(task.location || '—') +
                    '</td>' +
                    '<td class="whitespace-nowrap px-4 py-3 text-end">' +
                    flytoButton(task) +
                    '</td>';
                tbody.appendChild(tr);
            }

            if (!task.isOpen || !hasCoords(task)) return;

            var lat = +task.lat;
            var lng = +task.lng;
            pts.push([lat, lng]);

            var icon = L.divIcon({
                className: '',
                html: "<div class='saha-pin " + pinClass(task) + "'></div>",
                iconSize: [18, 18],
                iconAnchor: [9, 9],
                popupAnchor: [0, -12],
            });

            var marker = L.marker([lat, lng], { icon: icon }).addTo(map);
            marker.bindPopup(
                '<div style="min-width:200px;font-family:system-ui,sans-serif">' +
                    '<p style="font-weight:600;margin:0 0 6px">' +
                    esc(task.title || '') +
                    '</p>' +
                    '<p style="font-size:12px;color:#64748b;margin:0 0 10px">' +
                    esc(task.assignee_name || '—') +
                    '</p>' +
                    '<a href="' +
                    safeHref(detailUrl(task)) +
                    '" style="display:block;text-align:center;padding:6px 10px;border-radius:8px;background:#2563eb;color:#fff;font-size:12px;font-weight:600;text-decoration:none">' +
                    'Görevi aç' +
                    '</a>' +
                '</div>'
            );
            markers[String(task.id)] = marker;
        });

        if (tbody) {
            tbody.onclick = function (e) {
                var b = e.target.closest('.saha-flyto');
                if (!b) return;
                var id = b.getAttribute('data-id');
                var mk = markers[id];
                var task = TASKS.find(function (x) {
                    return String(x.id) === String(id);
                });
                if (!mk || !task || !hasCoords(task) || !task.isOpen) return;
                map.flyTo([+task.lat, +task.lng], 16, { duration: 1.2 });
                setTimeout(function () {
                    try {
                        mk.openPopup();
                    } catch (err) {}
                }, 650);
            };
        }

        if (pts.length > 1) {
            map.fitBounds(L.latLngBounds(pts).pad(0.12));
        } else if (pts.length === 1) {
            map.setView(pts[0], 15);
        }

        function doInvalidate() {
            setTimeout(function () {
                try {
                    if (typeof window.belScheduleMapInvalidate === 'function') {
                        window.belScheduleMapInvalidate(map, el, 200);
                    } else {
                        map.invalidateSize({ animate: false });
                    }
                } catch (e) {}
            }, 200);
        }

        doInvalidate();
    }

    function tabShowsSaha(trigger) {
        if (!trigger || !trigger.getAttribute) return false;
        var t = trigger.getAttribute('data-bs-target');
        var h = trigger.getAttribute('href');
        return t === '#saha-haritasi' || h === '#saha-haritasi';
    }

    if (!window.__belSahaTabBound) {
        window.__belSahaTabBound = true;
        document.addEventListener('shown.bs.tab', function (e) {
            if (!tabShowsSaha(e.target)) return;
            var el = document.getElementById('mapContainer');
            var m = el && el._sahaMap;
            if (m) {
                setTimeout(function () {
                    try {
                        m.invalidateSize({ animate: false });
                    } catch (err) {}
                }, 200);
            }
        });
    }

    function waitLeaflet(cb, n) {
        n = n || 0;
        if (typeof L !== 'undefined') {
            cb();
            return;
        }
        if (n > 80) {
            cb();
            return;
        }
        setTimeout(function () {
            waitLeaflet(cb, n + 1);
        }, 50);
    }

    waitLeaflet(function () {
        requestAnimationFrame(function () {
            requestAnimationFrame(initSahaMap);
        });
    });
})();
</script>
@endscript
