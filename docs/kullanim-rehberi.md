# Bel-Sistem Kullanım Rehberi (Arşiv)

Bu doküman tek parça rehberin arşiv sürümüdür.

Güncel ve ayrıştırılmış rehberler:

- `docs/kullanici-rehberi.md`
- `docs/yonetici-teknik-rehber.md`

---

Bu doküman, Bel-Sistem'in panel ve API tarafındaki tüm temel kullanım adımlarını tek yerde toplar.

## 1) Sistem Bileşenleri

- Yönetim paneli (Filament): `.../admin`
- REST servis arayüzü (v1): `.../api/v1`
- Denetim kayıtları (audit): panelde `Raporlar > Denetim kayıtları`
- 4-göz onay mekanizması: panelde `Raporlar > Onay talepleri`

## 2) İlk Kurulum ve Çalıştırma

### 2.1 Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

### 2.2 Geliştirme Ortamı

```bash
composer dev
```

Bu komut uygulama sunucusunu, kuyruk dinleyicisini, log izlemeyi ve Vite'ı birlikte başlatır.

## 3) Örnek Kullanıcılar ve Roller

Seed sonrası örnek hesaplar:

- `admin@kirklareli.bel.tr` / `password`
- `baskan.yardimcisi@kirklareli.bel.tr` / `password`
- `saha@kirklareli.bel.tr` / `password`

Roller:

- `Admin`: tam yönetim ve final onay
- `ViceMayor`: bağlı müdürlüklerde yönetim ve onay
- `Manager`: kendi müdürlüğünde görev/kullanıcı operasyonları
- `Staff`: kendisine atanmış görevleri sahada yürütme

## 4) Panel Menülerinin Kullanımı

## 4.1 Görevler

`Görevler` menüsü görev yaşam döngüsünün ana ekranıdır.

Başlıca alanlar:

- Görev kodu, başlık, müdürlük; atanan kişi ilgili müdürlükte tanımlı saha şefi kullanıcısıdır
- Adres metni ve isteğe bağlı harita üzerinden görev noktası
- Öncelik, durum, açıklama
- Çözüm notu ve kanıt fotoğrafları
- Atanma/sahaya çıkış/tamamlanma zamanları

Durumlar:

- `Bekliyor`
- `Yönlendirildi`
- `Sahada`
- `Çözüldü`
- `Onay bekliyor`
- `Kapatıldı`
- `Tamamlandı` (geriye dönük uyumluluk için)

Kurallar:

- Kapanışa giden adımlarda kanıt fotoğrafı ve çözüm notu zorunludur.
- Geçersiz durum geçişleri engellenir.
- Fiziksel silme kapalıdır, soft-delete yaklaşımı kullanılır.

## 4.2 Görev Detayı ve Saha İşlemleri

Görev detay ekranında:

- `Yol tarifi`: koordinat varsa harici rota açar.
- `Görevi tamamla`: cihaz konumunu alır ve görev noktasına mesafeyi doğrular.
- Görev kritikse veya SLA ihlali varsa durum `Onay bekliyor`a alınır ve onay talebi açılır.

## 4.3 Saha Haritası

`Saha Haritası` ekranı açık görevleri harita üzerinde gösterir.

- Haritada yalnızca açık ve koordinatlı görevler işaretlenir.
- Liste satırından `Haritada gör` ile ilgili noktaya odaklanılır.
- Kapsam, giriş yapan kullanıcının rolüne göre filtrelenir.

## 4.4 Müdürlükler

`Müdürlükler` ekranı birim yönetimi içindir.

- Müdürlük adı, başkan yardımcısı ataması
- Müdür/saha şefi iletişim alanları
- Personel sayısı

Not: Rolüne bağlı olarak telefon alanları maskeleme ile gösterilebilir.

## 4.5 Kullanıcılar

`Kullanıcılar` ekranı hesap yönetimi içindir.

- Ad, e-posta, rol, müdürlük
- Rol ataması yetki seviyesine göre sınırlandırılır.
- Bazı rol değişiklikleri doğrudan uygulanmaz, onaya gönderilir.

## 4.6 Başkan Yardımcısı Raporu

`Raporlar > Başkan yardımcısı raporu` ekranı özet KPI ve grafikleri sunar:

- Personel ve aktif görev özetleri
- Müdürlüklere göre tamamlanan görevler
- Durum dağılımı

## 4.7 Denetim Kayıtları

`Raporlar > Denetim kayıtları` ekranında:

- Kim, ne zaman, hangi işlem yaptı bilgisi
- Varlık tipi, işlem tipi, tarih aralığı filtreleri
- Yetkili roller için CSV dışa aktarma

## 4.8 Onay Talepleri

`Raporlar > Onay talepleri` ekranı 4-göz süreçlerini yönetir:

- Bekleyen talepleri listeleme
- `Onayla` / `Reddet` aksiyonları
- Talebin türüne göre hedef kayda etkisini uygulama

Örnek talep türleri:

- `task_close`
- `user_role_change`
- `department_vice_mayor_change`

## 4.9 API Rehberi (Panel İçi)

`Sistem > API Rehberi` ekranı:

- API temel adresi
- Token alma örneği
- Temel endpoint listesi
- OpenAPI dosyası bağlantısı (`/docs/openapi.yaml`)

## 5) API Kullanım Rehberi

## 5.1 Kimlik Doğrulama

Token alma:

```http
POST /api/v1/auth/token
Content-Type: application/json

{
  "email": "admin@kirklareli.bel.tr",
  "password": "password",
  "device_name": "postman"
}
```

Sonraki isteklerde:

```http
Authorization: Bearer <erisim_belirteci>
```

## 5.2 Mevcut Endpointler

- `POST /api/v1/auth/token`
- `POST /api/v1/auth/revoke`
- `GET /api/v1/me`
- `GET /api/v1/tasks`
- `GET /api/v1/tasks/{task}`
- `POST /api/v1/tasks`
- `PATCH /api/v1/tasks/{task}`
- `GET /api/v1/departments`
- `GET /api/v1/departments/{department}`
- `GET /api/v1/users`
- `GET /api/v1/users/{user}`
- `GET /api/v1/approval-requests`
- `GET /api/v1/approval-requests/{approvalRequest}`
- `POST /api/v1/approval-requests/{approvalRequest}/approve`
- `POST /api/v1/approval-requests/{approvalRequest}/reject`

## 6) Uçtan Uca İş Akışı Örneği

1. Yönetici veya birim yöneticisi görev açar.
2. Görev `Yönlendirildi` durumuna alınır, personel atanır.
3. Personel sahaya çıkıp görevi yürütür (`Sahada`).
4. Personel kanıt ve çözüm notu ile tamamlama adımını çalıştırır.
5. Kritik/SLA ihlal durumunda görev `Onay bekliyor`a düşer ve onay talebi açılır.
6. Yetkili kişi onay talebini `Onayla` derse görev `Kapatıldı` olur; `Reddet` derse tekrar işleme döner.
7. Tüm adımlar denetim kayıtlarına işlenir.

## 7) Test ve Kalite Kontrol

```bash
php artisan test
```

Öneri:

- Her dağıtımdan önce test çalıştırın.
- Özellikle yetkilendirme, onay akışı ve durum geçiş testlerini zorunlu kontrolde tutun.

## 8) Sık Karşılaşılan Sorunlar

- Haritada görev görünmüyorsa:
  - Görevin koordinatı var mı?
  - Görev açık mı?
  - Kullanıcının rol kapsamına giriyor mu?

- Saha tamamla butonu çalışmıyorsa:
  - Tarayıcı konum izni açık mı?
  - GPS doğruluğu yeterli mi?
  - Kapanış için kanıt fotoğrafı yüklendi mi?

- API 401 dönüyorsa:
  - Bearer erişim belirteci geçerli mi?
  - Erişim belirteci iptal edilmiş olabilir; yeni belirteç alın.

## 9) Referans Dosyalar

- API route tanımları: `routes/api.php`
- Panel API rehberi sayfası: `app/Filament/Pages/ApiGuide.php`
- API rehberi görünümü: `resources/views/filament/pages/api-guide.blade.php`
- OpenAPI dokümanı: `public/docs/openapi.yaml`
- KVKK/denetim kontrol listesi: `docs/kvkk-ve-denetim-kontrol-listesi.md`
