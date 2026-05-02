# Bel-Sistem Yönetici ve Teknik Rehber

Bu rehber sistem yöneticileri, başkan yardımcısı rolündeki denetleyiciler ve teknik ekip içindir.

## 1) Rol ve Yetki Modeli

Roller:

- `Admin`: sistem genel yönetim
- `ViceMayor`: bağlı müdürlükler üzerinde geniş yetki
- `Manager`: kendi müdürlüğü kapsamı
- `Staff`: atandığı görev kapsamı

Temel prensip:

- Yetkilendirme ilke katmanında en az yetki yaklaşımı ile uygulanır.
- Veri görünürlüğü role + kapsam koşulları ile filtrelenir.

## 2) Denetim ve Onay Mekanizması

## 2.1 Denetim Kayıtları

- Kritik değişiklikler activity log'a yazılır.
- Panel: `Raporlar > Denetim kayıtları`
- Filtreler: varlık tipi, işlem tipi, tarih aralığı
- CSV dışa aktarma: yetkili roller

## 2.2 Onay Talepleri (4-Göz)

Panel: `Raporlar > Onay talepleri`

Örnek talep tipleri:

- `task_close`
- `user_role_change`
- `department_vice_mayor_change`

Süreç:

1. Talep `beklemede` oluşur.
2. Yetkili kişi onaylar veya reddeder.
3. Onay sonucu hedef kayda uygulanır ve audit izi oluşur.

## 3) API Rehberi

## 3.1 Kimlik Doğrulama

```http
POST /api/v1/auth/token
```

Dönen erişim belirteci, `Authorization: Bearer <erisim_belirteci>` ile kullanılır.

## 3.2 Endpoint Grupları

- Auth: `/auth/token`, `/auth/revoke`, `/me`
- Tasks: `/tasks`, `/tasks/{id}`
- Departments: `/departments`, `/departments/{id}`
- Users: `/users`, `/users/{id}`
- Approval Requests: `/approval-requests`, `/approval-requests/{id}/approve|reject`

OpenAPI dokümanı:

- `.../docs/openapi.yaml`
- Panelden: `Sistem > API Rehberi`

## 4) İş Akışı ve Durum Yönetimi

Durumlar:

- `Bekliyor`
- `Yonlendirildi`
- `Sahada`
- `Cozuldu`
- `OnayBekliyor`
- `Kapatildi`

Kapanışta zorunlular:

- Çözüm notu
- Kanıt fotoğrafları
- Zaman tutarlılığı

## 5) Operasyonel Kontroller

- Periyodik test: `php artisan test`
- Migration sonrası hızlı doğrulama:
  - Panel giriş
  - Görev listeleme
  - API erişim belirteci alma
  - Onay talebi ekranı

## 6) Teknik Referanslar

- API route: `routes/api.php`
- Panel API sayfası: `app/Filament/Pages/ApiGuide.php`
- Denetim: `app/Filament/Resources/AuditLogResource.php`
- Onay: `app/Filament/Resources/ApprovalRequestResource.php`
