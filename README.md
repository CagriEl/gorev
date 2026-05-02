# Bel-Sistem

Kamu kurumlarında (il belediyesi) saha görev takibi için Laravel + Filament tabanlı yönetim paneli.

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

## Geliştirme Komutları

```bash
composer dev
php artisan test
```

## Güvenlik ve Yetkilendirme

- Rol modeli: `Admin`, `ViceMayor`, `Manager`, `Staff`.
- Yetki katmanı en az yetki prensibi ile sınırlandırılmıştır.
- Kritik işlemler için ikinci onay (4-göz) akışı `approval_requests` tablosunda izlenir.

## Denetim Kayıtları

- `spatie/laravel-activitylog` ile görev, kullanıcı ve müdürlük değişiklikleri izlenir.
- Denetim kayıtlarına panelden `Denetim kayıtları` ekranı ile erişilir.
- CSV dışa aktarma yalnızca yetkili roller için açıktır.

## Süreç Bütünlüğü

- Durum akışı: `Bekliyor -> Yonlendirildi -> Sahada -> Cozuldu -> OnayBekliyor -> Kapatildi`.
- Kapanışta çözüm notu ve kanıt fotoğrafı zorunludur.
- Yumuşak silme aktiftir; fiziksel silme varsayılan akışta kapalıdır.

## Dokümantasyon

- Kullanıcı rehberi: `docs/kullanici-rehberi.md`
- Yönetici/teknik rehber: `docs/yonetici-teknik-rehber.md`
- Arşiv tek parça rehber: `docs/kullanim-rehberi.md`
- KVKK/denetim kontrol listesi: `docs/kvkk-ve-denetim-kontrol-listesi.md`
