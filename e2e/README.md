# Playwright — Tam sistem demosu

Bel-Sistem panelini **baştan sona** sırayla çalıştıran makro (video kaydı açık).

## Kapsam

1. Admin: gösterge paneli, **yeni müdürlük**, **yeni kullanıcılar** (müdür + saha şefi)  
2. Yapay zeka: sınıflandırıcı + eğitim örnekleri (ML API kapalıysa tahmin atlanır)  
3. Görev: oluştur → fotoğraf → yönlendir → sahada → çöz → kapat  
4. Saha haritası, başkan yardımcısı raporu, onay talepleri, denetim kayıtları  
5. Kullanıcı / API rehberi  
6. Başkan yardımcısı, birim müdürü, saha şefi oturumları  
7. REST API token + görev listesi  

## Kurulum

```bash
# Proje kökü (Valet / Herd / nginx ile gorev.test)
# .env: APP_URL=http://gorev.test
php artisan storage:link

# E2E
cd e2e
cp .env.example .env
npm install
npx playwright install chromium
```

Kullanıcı verisi (CSV import sonrası):

- `admin@admin.com` / `password`
- `aydemircan@kirklareli.bel.tr` — başkan yardımcısı
- `fen-isleri-mudurlugu@kirklareli.bel.tr` — müdür
- `fen@kirklareli.bel.tr` — saha şefi  

ML tahmin adımı için (opsiyonel): `ml/mudurluk-siniflandirici/scripts/start_api.sh`

## Çalıştırma

```bash
cd e2e
npm run test:full:slow    # izlemek için (yavaş + video)
npm run test:full         # headed, normal hız
npm test                  # headless
```

`PLAYWRIGHT_BASE_URL` — `e2e/.env` içinde varsayılan `http://gorev.test` (Cursor/LLM tarayıcısı `127.0.0.1` ile çalışmıyorsa bunu kullanın).

Rapor: `npm run report` → `playwright-report/index.html`
