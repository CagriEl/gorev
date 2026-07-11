# Bel-Sistem Mobil (Expo)

iOS ve Android için tek kod tabanı — **Expo + TypeScript**.

## Roller

| Rol | Ekran |
|-----|--------|
| **Personel** (`staff`) | Görevlerim → detay → sahada / tamamla (kamera) |
| **Başkan yardımcısı / Müdür** | **Görevler** sekmesi (bağlı müdürlükler) + **Rapor** özeti |
| **Admin** | Rapor (müdür ile aynı) |

## Kurulum

```bash
cd mobile
cp .env.example .env
npm install
npx expo start
```

- **iOS simülatör:** `i` tuşu veya `npm run ios`
- **Android emülatör:** `a` tuşu veya `npm run android`
- **Fiziksel telefon:** Expo Go ile QR kod (aynı Wi‑Fi)

## API adresi

`.env` içinde:

```env
EXPO_PUBLIC_API_URL=http://gorev.test/api/v1
```

Gerçek cihazda `gorev.test` çözülmezse bilgisayar IP’nizi kullanın:

```env
EXPO_PUBLIC_API_URL=http://192.168.1.10/api/v1
```

(Laravel Valet/Herd’de site `gorev.test` olarak dinlemeli; IP üzerinden de erişim açık olmalı.)

## iOS (Xcode) — hata kodu 70

**Belirti:** `Unable to find a destination` veya `iOS 26.x is not installed`.

**Sebep:** Xcode güncel SDK (ör. 26.2) ile yüklü simülatör runtime (ör. yalnızca 26.1) uyuşmuyor.

**Çözüm (kalıcı):**

1. **Xcode** → **Settings** → **Platforms** (veya **Components**)
2. **iOS 26.2 Simulator** (Xcode sürümünüzle aynı) indirin — birkaç GB sürebilir
3. **Window** → **Devices and Simulators** → **+** → yeni iPhone simülatörü (26.2 runtime)
4. Terminal:
   ```bash
   cd mobile
   npx expo run:ios
   ```
   Belirli cihaz için: `npx expo run:ios --device "iPhone 16"`

Komut satırından indirme (alternatif):

```bash
xcodebuild -downloadPlatform iOS
```

**Geçici çözüm (native derleme olmadan):**

```bash
npx expo start
# terminalde i → Expo Go simülatörde açılır
```

## Test hesapları

| Rol | E-posta | Şifre |
|-----|---------|--------|
| Saha şefi | `fen@kirklareli.bel.tr` | `password` |
| Fen müdürü | `fen-isleri-mudurlugu@kirklareli.bel.tr` | `password` |
| Başkan yardımcısı | `aydemircan@kirklareli.bel.tr` | `password` |

## Backend gereksinimleri

Laravel tarafında:

```bash
php artisan migrate
php artisan storage:link
```

Yeni API uçları:

- `POST /api/v1/uploads/task-photo` — multipart fotoğraf
- `GET /api/v1/reports/dashboard` — müdür / başkan yardımcısı raporu
- `POST /api/v1/push-tokens` — cihaz push belirteci kaydı
- `POST /api/v1/push-tokens/revoke` — çıkışta belirteç silme

## Push bildirimleri (uygulama kapalıyken)

Görev atandığında telefona bildirim gitmesi için:

1. Uygulamaya giriş yapın — **bildirim izni otomatik sorulur** (İzin ver deyin).
2. Push belirteci sunucuya kaydedilir; panelden görev atanınca telefona düşer.

**Simülatör (iOS 17 + Expo Go):** İzin penceresi çıkar, ancak uzak push çoğu simülatörde **çalışmaz**. Gerçek test için fiziksel iPhone önerilir.

**Filament panel zili** (tarayıcı `gorev.test/admin`) ile **telefon bildirim paneli** farklıdır:
- Panel zili → `fen@kirklareli.bel.tr` ile **web panelde** giriş yapınca görünür.
- Telefon → mobil uygulama + push izni + kayıtlı cihaz gerekir.

**APK / native build** için EAS projesi gerekir:

```bash
cd mobile
npx eas init          # projectId üretir → app.json extra.eas.projectId
npm run apk:cloud     # veya npx expo run:android
```

Expo Go ile de test edilebilir; giriş sonrası bildirim iznini onaylayın.

Bildirime dokununca uygulama ilgili görev detayına açılır.

## Doğrudan APK (Expo Go olmadan)

### Yol A — Bulut (önerilen, Android Studio gerekmez)

```bash
cd mobile
npm install -g eas-cli
eas login
```

`eas.json` → `preview` profilinde `EXPO_PUBLIC_API_URL` değerini telefonun erişebileceği adrese çevirin (IP veya HTTPS domain; `gorev.test` telefonda çalışmaz).

```bash
npm run apk:cloud
```

İş bitince Expo sitesinden **APK indir** → telefona kur.

### Yol B — Yerel (Android Studio + JDK 17)

```bash
cd mobile
npx expo prebuild --platform android
cd android && ./gradlew assembleDebug
```

APK: `android/app/build/outputs/apk/debug/app-debug.apk`

## Mağaza derlemesi (ileride)

```bash
npm install -g eas-cli
eas build --platform all
```

## Proje yapısı

```
mobile/
  App.tsx
  src/
    api/client.ts      # REST istemcisi
    auth/AuthContext.tsx
    navigation/
    screens/           # Login, Tasks, TaskDetail, Reports
    types.ts
```
