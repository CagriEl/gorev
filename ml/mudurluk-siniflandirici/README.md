# Müdürlük sınıflandırıcı (BERTurk)

Vatandaş şikâyet metnini Gorev müdürlük slug'ına eşler (`FEN_ISLERI`, `TEMIZLIK_ISLERI`, …).

## Hızlı başlangıç (kendi sunucu)

```bash
cd ml/mudurluk-siniflandirici
chmod +x scripts/*.sh
./scripts/setup_and_train.sh   # venv, veri, eğitim (~CPU'da birkaç dakika)
./scripts/start_api.sh         # http://127.0.0.1:8100
```

Sağlık kontrolü: `GET http://127.0.0.1:8100/health`

Tahmin:

```bash
curl -s -X POST http://127.0.0.1:8100/predict \
  -H 'Content-Type: application/json' \
  -d '{"text":"kaldırım taşları çöktü"}' | jq
```

## Gorev veritabanından eğitim

`ml/mudurluk-siniflandirici/.env` içine Gorev ile aynı `DB_*` değerlerini yazın veya üst dizindeki `.env` okunur.

```bash
python data_loader.py    # tasks + departments
python train.py
python evaluate.py
```

Veritabanı boşsa otomatik `data/sample_train.csv` kullanılır.

### Belediye şikâyet örnek seti

`data/belediye_sikayetleri.csv` — Fen, Temizlik, Park-Bahçe ve Veteriner için ~100 gerçekçi örnek cümle. Eğitimde `data_loader.py` bu dosyayı otomatik birleştirir.

Panele aktarmak (Gorev):

```bash
php artisan classifier:import-samples
php artisan classifier:retrain
```

Kendi CSV'nizi eklemek için aynı sütunlar: `sikayet_metni,mudurluk_slug` ve `php artisan classifier:import-samples --file=/yol/dosya.csv`.

Kapalı görevler de eğitime girer: `tasks` tablosunda durumu `kapatildi`, `tamamlandi`, `cozuldu` veya `onay_bekliyor` olan kayıtlar.

## Gorev (Laravel) entegrasyonu

`.env`:

```
ML_CLASSIFIER_URL=http://127.0.0.1:8100
ML_CLASSIFIER_API_KEY=
```

`App\Services\MudurlukClassifierService` ile `predict()` çağrılır; dönen `department_slug` → `departments` tablosunda eşleştirilip görev oluşturulur.

## Üretim notları

- `API_KEY` tanımlayın; isteklerde `X-Api-Key` header.
- `confidence < CONFIDENCE_THRESHOLD` → `needs_review: true` (otomatik atama yapmayın).
- Model klasörünü yedekleyin: `kaydedilen_model/`.
- Docker: `docker build -t bel-mudurluk-ml . && docker run -p 8100:8100 -v $(pwd)/kaydedilen_model:/app/kaydedilen_model bel-mudurluk-ml`
