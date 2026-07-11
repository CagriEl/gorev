# Bel-Sistem — Sunum rehberi

Sunumdan önce demo verisini temizleyin; canlı olarak **müdürlük → kullanıcılar → görev** oluşturmayı gösterin.

## Sunum öncesi

```bash
php artisan gorev:sunum-sifirla --force
php artisan storage:link   # ilk kurulumda
```

Bu komut görevleri, denetim kayıtlarını ve bir önceki **Sunum Demo** müdürlüğünü/kullanıcılarını siler. CSV’deki 21 müdürlük ve mevcut personel **korunur**.

Panel: **http://gorev.test/admin** · `admin@admin.com` / `password`

ML tahmin (opsiyonel): `ml/mudurluk-siniflandirici/scripts/start_api.sh`

---

## Canlı sunum akışı (~20 dk)

### 1. Yeni müdürlük

1. **Müdürlükler** → **Oluştur**
2. Örnek:
   - **Müdürlük adı:** `Sunum Demo Müdürlüğü`
   - **Başkan yardımcısı:** listeden seçin (ör. Aydemir CAN)
   - **Birim müdürü:** `Sunum Demo Müdür` · tel. `0288 000 00 01`
   - **Saha şefi (iletişim):** `Sunum Demo Saha Şefi` · tel. `0288 000 00 02`
   - **Personel sayısı:** `12`
3. **Kaydet**

### 2. Kullanıcılar (müdürlüğe bağlı)

**Birim yöneticisi**

1. **Kullanıcılar** → **Oluştur**
2. Ad: `Sunum Demo Müdür` · E-posta: `sunum-mudur@kirklareli.bel.tr` · Şifre: `password`
3. Rol: **Birim yöneticisi** · Müdürlük: **Sunum Demo Müdürlüğü** → Kaydet

**Saha şefi (personel)**

1. Yine **Oluştur**
2. Ad: `Sunum Demo Saha Şefi` · E-posta: `sunum-saha@kirklareli.bel.tr` · Şifre: `password`
3. Rol: **Personel** · Müdürlük: **Sunum Demo Müdürlüğü** → Kaydet

**Müdürlüğe saha şefi hesabını bağlama**

1. **Müdürlükler** → `Sunum Demo Müdürlüğü` → düzenle
2. **Saha şefi kullanıcısı:** `Sunum Demo Saha Şefi` → **Kaydet**  
   (Görevler bu kullanıcıya atanır.)

### 3. Yeni görev (yeni müdürlükte)

1. **Görevler** → **Oluştur**
2. Başlık: `Cumhuriyet Caddesi çöp konteyneri`
3. **Müdürlük:** `Sunum Demo Müdürlüğü`
4. Adres + **haritadan nokta seçin**
5. Öncelik Normal · Durum Bekliyor · Açıklama: `Çöpler çok dolu, sokak kokuyor.`
6. **Göreve varış fotoğrafı** → fotoğraf yükle → **Kaydet**

### 4. Yapay zeka (varsa)

- **Müdürlük sınıflandırıcı** → aynı metin → **Tahmin et**

### 5. Saha haritası

- **Saha Haritası** → az önceki görev **pin** olarak görünür

### 6. Görev durumu (kısa)

- Yönlendirildi → Sahada → Çözüldü → Kapatıldı

### 7. Roller

| Oturum | E-posta | Şifre |
|--------|---------|--------|
| Başkan yardımcısı | `aydemircan@kirklareli.bel.tr` | `password` |
| Sunum müdürü | `sunum-mudur@kirklareli.bel.tr` | `password` |
| Sunum saha şefi | `sunum-saha@kirklareli.bel.tr` | `password` |

### 8. Kapanış

- Denetim kayıtları · Kullanıcı / API rehberi

---

## Otomatik prova (Playwright)

```bash
cd e2e
npm test
```

Test, yukarıdaki müdürlük/kullanıcı/görev adımlarını `http://gorev.test` üzerinde sırayla çalıştırır.

---

## Sunum sonrası

```bash
php artisan gorev:sunum-sifirla --force
```

Sunum sırasında oluşturduğunuz **Sunum Demo** kayıtları da silinir; CSV müdürlükleri kalır.

---

## Sorun giderme

| Sorun | Çözüm |
|--------|--------|
| E-posta zaten kayıtlı | `gorev:sunum-sifirla --force` (demo org silinir) |
| Görev kodu çakışması | Aynı sıfırlama komutu |
| Haritada pin yok | Görevde haritadan konum seçin |
| Saha şefi atanmıyor | Müdürlük düzenle → saha şefi kullanıcısı |
