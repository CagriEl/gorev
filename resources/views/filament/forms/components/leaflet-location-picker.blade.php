@php
    $lat = (float) ($lat ?? 41.7351);
    $lng = (float) ($lng ?? 27.2252);
    $livewireId = $field->getLivewire()->getId();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @once
        @push('styles')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        @endpush
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        @endpush
    @endonce

    <div
        wire:ignore
        x-data="leafletTaskLocation({
            livewireId: @js($livewireId),
            initialLat: {{ number_format($lat, 7, '.', '') }},
            initialLng: {{ number_format($lng, 7, '.', '') }},
        })"
        x-init="init()"
        @leaflet-sync-from-form.window="syncFromForm()"
        class="w-full space-y-3"
    >
        <div class="flex flex-wrap items-center gap-2">
            <x-filament::button
                type="button"
                color="gray"
                size="sm"
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
            Haritaya tıklayın veya işareti sürükleyin; koordinatlar yukarıdaki alanlara yazılır. Elle girdiğiniz değerleri haritaya yansıtmak için alan dışına tıklayın.
        </p>
    </div>
</x-dynamic-component>

@once
    @push('scripts')
        <script>
            (function registerLeafletTaskLocation() {
                const factory = (config) => ({
                    livewireId: config.livewireId,
                    initialLat: config.initialLat,
                    initialLng: config.initialLng,
                    map: null,
                    marker: null,

                    notify(title, body, status = 'danger') {
                        const F = window.FilamentNotification;
                        if (typeof F === 'function') {
                            const n = new F().title(title).body(body);
                            if (status === 'success') {
                                n.success();
                            } else if (status === 'warning') {
                                n.warning();
                            } else if (status === 'info') {
                                n.info();
                            } else {
                                n.danger();
                            }
                            n.send();
                        } else {
                            window.alert(title + (body ? '\n\n' + body : ''));
                        }
                    },

                    wire() {
                        return window.Livewire.find(this.livewireId);
                    },

                    readCoord(path) {
                        const w = this.wire();
                        if (!w || typeof w.$get !== 'function') {
                            return NaN;
                        }
                        const v = w.$get(path);
                        if (v === null || v === undefined || v === '') {
                            return NaN;
                        }
                        return parseFloat(v);
                    },

                    syncToForm(ll) {
                        const w = this.wire();
                        if (!w || typeof w.$set !== 'function') {
                            return;
                        }
                        const lat = parseFloat(ll.lat.toFixed(7));
                        const lng = parseFloat(ll.lng.toFixed(7));
                        w.$set('data.latitude', lat, true);
                        w.$set('data.longitude', lng, true);
                    },

                    syncFromForm() {
                        if (!this.map || !this.marker) {
                            return;
                        }
                        const lat = this.readCoord('data.latitude');
                        const lng = this.readCoord('data.longitude');
                        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                            return;
                        }
                        this.marker.setLatLng([lat, lng]);
                        this.map.setView([lat, lng], Math.max(this.map.getZoom(), 14));
                    },

                    init() {
                        const run = () => {
                            if (typeof L === 'undefined' || !this.$refs.mapEl) {
                                requestAnimationFrame(run.bind(this));
                                return;
                            }
                            const el = this.$refs.mapEl;
                            if (!el.isConnected) {
                                return;
                            }

                            let lat = this.initialLat;
                            let lng = this.initialLng;
                            const wl = this.readCoord('data.latitude');
                            const wn = this.readCoord('data.longitude');
                            if (Number.isFinite(wl) && Number.isFinite(wn)) {
                                lat = wl;
                                lng = wn;
                            }

                            this.map = L.map(el).setView([lat, lng], 14);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap',
                            }).addTo(this.map);

                            this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                            this.marker.on('dragend', (e) => this.syncToForm(e.target.getLatLng()));
                            this.map.on('click', (e) => {
                                this.marker.setLatLng(e.latlng);
                                this.map.panTo(e.latlng);
                                this.syncToForm(e.latlng);
                            });

                            setTimeout(() => this.map.invalidateSize(), 250);
                        };
                        run();
                    },

                    locateMe() {
                        if (!window.isSecureContext) {
                            this.notify(
                                'Güvenli bağlantı gerekli',
                                'Tarayıcılar konum API’sini yalnızca HTTPS (veya localhost) üzerinde sunar. Lütfen siteyi HTTPS ile açın.',
                                'warning',
                            );
                            return;
                        }

                        if (!navigator.geolocation) {
                            this.notify(
                                'Konum desteklenmiyor',
                                'Bu tarayıcı veya cihaz Geolocation API desteklemiyor.',
                                'danger',
                            );
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const ll = {
                                    lat: pos.coords.latitude,
                                    lng: pos.coords.longitude,
                                };
                                if (!this.map || !this.marker) {
                                    return;
                                }
                                this.marker.setLatLng(ll);
                                this.map.setView([ll.lat, ll.lng], 16);
                                this.syncToForm(ll);
                                this.notify('Konum alındı', 'Harita ve koordinat alanları güncellendi.', 'success');
                            },
                            (err) => {
                                let title = 'Konum alınamadı';
                                let body =
                                    'Konum izni verilmedi, GPS kapalı veya sinyal yok. Ayarlardan konum iznini kontrol edin.';

                                if (err && typeof err.code === 'number') {
                                    switch (err.code) {
                                        case err.PERMISSION_DENIED:
                                            title = 'Konum izni reddedildi';
                                            body =
                                                'Tarayıcı veya sistem konum erişimine izin vermiyor. Adres çubuğundaki kilit simgesinden izni açmayı deneyin.';
                                            break;
                                        case err.POSITION_UNAVAILABLE:
                                            title = 'Konum bilgisi yok';
                                            body =
                                                'Cihaz konumunuzu şu an belirleyemiyor (GPS kapalı veya kapalı ortam olabilir).';
                                            break;
                                        case err.TIMEOUT:
                                            title = 'Konum zaman aşımı';
                                            body =
                                                'Konum isteği çok uzun sürdü; tekrar deneyin veya açık alanda deneyin.';
                                            break;
                                        default:
                                            break;
                                    }
                                }

                                this.notify(title, body, 'warning');
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 20000,
                                maximumAge: 0,
                            },
                        );
                    },
                });

                const register = () => {
                    if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
                        Alpine.data('leafletTaskLocation', factory);
                    }
                };

                if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
                    register();
                } else {
                    document.addEventListener('alpine:init', register, { once: true });
                }
            })();
        </script>
    @endpush
@endonce
