<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

<style>
    .bel-status-pin {
        width: 14px;
        height: 14px;
        border-radius: 9999px;
        border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.35);
    }
    /* Bootstrap benzeri lejant: Sahada=warning, Tamamlandı=success, Yönlendirildi=info, Bekliyor=secondary */
    .bel-status-pin--bekliyor { background: #6c757d; }
    .bel-status-pin--yonlendirildi { background: #0dcaf0; }
    .bel-status-pin--sahada { background: #ffc107; }
    .bel-status-pin--tamamlandi { background: #198754; }
</style>
