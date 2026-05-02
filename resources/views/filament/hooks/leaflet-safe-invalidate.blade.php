<script>
    /**
     * Leaflet invalidateSize: _leaflet_pos hatasını önlemek için harita + konteyner canlı mı kontrol edilir.
     */
    window.belSafeInvalidateSize = function (map, containerEl) {
        if (!map || typeof map.invalidateSize !== 'function') {
            return;
        }
        if (!containerEl || !containerEl.isConnected) {
            return;
        }
        var getC = map.getContainer;
        if (typeof getC !== 'function') {
            return;
        }
        var c = getC.call(map);
        if (!c || !c.isConnected) {
            return;
        }
        try {
            if (typeof map.whenReady === 'function') {
                map.whenReady(function () {
                    try {
                        if (!containerEl.isConnected) {
                            return;
                        }
                        if (
                            containerEl._belLeafletMap !== undefined &&
                            containerEl._belLeafletMap !== null &&
                            containerEl._belLeafletMap !== map
                        ) {
                            return;
                        }
                        var c2 = map.getContainer();
                        if (!c2 || !c2.isConnected) {
                            return;
                        }
                        map.invalidateSize({ animate: false });
                    } catch (e) {}
                });
            } else {
                map.invalidateSize({ animate: false });
            }
        } catch (e) {}
    };

    window.belScheduleMapInvalidate = function (map, containerEl, delayMs) {
        if (containerEl._belInvalidateTimer) {
            clearTimeout(containerEl._belInvalidateTimer);
            containerEl._belInvalidateTimer = null;
        }
        containerEl._belInvalidateTimer = setTimeout(function () {
            containerEl._belInvalidateTimer = null;
            if (!containerEl.isConnected) {
                return;
            }
            if (
                containerEl._belLeafletMap !== undefined &&
                containerEl._belLeafletMap !== null &&
                containerEl._belLeafletMap !== map
            ) {
                return;
            }
            window.belSafeInvalidateSize(map, containerEl);
        }, delayMs || 300);
    };
</script>
