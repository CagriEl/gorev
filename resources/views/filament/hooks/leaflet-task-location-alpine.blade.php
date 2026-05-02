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
                    this.notify(
                        'Geçersiz koordinat',
                        'Enlem −90 ile 90, boylam −180 ile 180 arasında sayı olmalıdır. Alanları kontrol edin.',
                        'warning',
                    );
                    return;
                }
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    this.notify('Koordinat aralığı dışında', 'Enlem veya boylam izin verilen aralığın dışında.', 'warning');
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

                    if (el._belPickerResizeHandler) {
                        window.removeEventListener('resize', el._belPickerResizeHandler);
                        el._belPickerResizeHandler = null;
                    }
                    if (el._belInvalidateTimer) {
                        clearTimeout(el._belInvalidateTimer);
                        el._belInvalidateTimer = null;
                    }
                    if (this.map) {
                        try {
                            this.map.remove();
                        } catch (e) {}
                        this.map = null;
                        this.marker = null;
                    }

                    let lat = this.initialLat;
                    let lng = this.initialLng;
                    const wl = this.readCoord('data.latitude');
                    const wn = this.readCoord('data.longitude');
                    if (Number.isFinite(wl) && Number.isFinite(wn)) {
                        lat = wl;
                        lng = wn;
                    }

                    try {
                        this.map = L.map(el).setView([lat, lng], 14);
                    } catch (e) {
                        this.notify('Harita başlatılamadı', 'Sayfayı yenileyin veya daha sonra tekrar deneyin.', 'danger');
                        return;
                    }
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

                    const safeInv = window.belSafeInvalidateSize;
                    const sched = window.belScheduleMapInvalidate;
                    if (typeof this.map.whenReady === 'function') {
                        this.map.whenReady(() => {
                            requestAnimationFrame(() => {
                                requestAnimationFrame(() => {
                                    if (typeof safeInv === 'function') {
                                        safeInv(this.map, el);
                                    }
                                });
                            });
                        });
                    }
                    if (typeof sched === 'function') {
                        sched(this.map, el, 280);
                    } else if (typeof safeInv === 'function') {
                        setTimeout(() => safeInv(this.map, el), 280);
                    }

                    el._belPickerResizeHandler = () => {
                        if (this.map && el.isConnected) {
                            if (typeof safeInv === 'function') {
                                safeInv(this.map, el);
                            }
                        }
                    };
                    window.addEventListener('resize', el._belPickerResizeHandler);
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
