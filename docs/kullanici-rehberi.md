# Bel-Sistem Kullanıcı Rehberi

Bu rehber saha personeli, birim yöneticisi ve operasyon kullanıcıları için hazırlanmıştır.

## 1) Giriş

- Panel adresine gidin: `.../admin`
- Kurum hesabınız ile giriş yapın.
- Rolünüze göre görebileceğiniz menüler farklı olabilir.

## 2) Ana Menüler

- `Görevler`
- `Saha Haritası`
- `Müdürlükler` (rol yetkisine göre)
- `Kullanıcılar` (rol yetkisine göre)
- `Raporlar`

## 3) Görev İşlemleri

## 3.1 Görev Açma

`Görevler > Yeni görev` ekranında:

- Başlık, müdürlük, öncelik ve durum girin.
- Müdürlük seçildiğinde **atanan** alanında o müdürlükte tanımlı **saha şefi kullanıcısı** otomatik gelir (Müdürlükler kaydında «Saha şefi kullanıcısı» atanmış olmalıdır).
- Konum için adres metnini girin; isteğe bağlı olarak haritadan nokta işaretleyin veya «Şu anki konumumu getir» ile konumu kaydedin (yol tarifi ve saha doğrulaması için).

## 3.2 Görev Güncelleme

- Durum, zaman ve açıklama alanlarını güncelleyebilirsiniz; atanan kişi her zaman ilgili müdürlüğün saha şefi kullanıcısıdır.
- Durum geçişleri sistem kurallarına göre kontrol edilir.

## 3.3 Kapanış Bilgileri

Kapanış adımlarında:

- Çözüm notu girin.
- Kanıt fotoğrafı yükleyin.
- Zaman alanlarının tutarlı olmasına dikkat edin.

## 4) Saha Haritası Kullanımı

- Açık ve koordinatı olan görevler haritada görünür.
- Görev listesinden `Haritada gör` ile ilgili noktaya odaklanabilirsiniz.
- Haritada görev detayını açarak kayıt sayfasına geçebilirsiniz.

## 5) Saha Tamamlama Akışı

Görev detayında `Görevi tamamla`:

- Cihaz konumu alınır.
- Görev noktasına mesafe kontrol edilir.
- Mesafe uygunsa görev kapatma akışı başlar.
- Kritik/SLA ihlalli görevler ikinci onaya düşebilir.

## 6) Onay Bekleyen Kapanışlar

Bazı görevler doğrudan kapanmaz:

- Durum `Onay bekliyor` olur.
- Yetkili onayı sonrası `Kapatıldı` olur.
- Reddedilirse görev yeniden işleme alınır.

## 7) Raporlar

Rapor ekranlarında:

- Görev yoğunluğu
- Durum dağılımı
- Müdürlük bazlı çıktı

gibi özet metrikleri izleyebilirsiniz.

## 8) Sık Sorunlar

- Görev haritada yoksa: koordinat veya durum kontrol edin.
- Tamamlama olmuyorsa: konum izni ve kanıt fotoğrafı kontrol edin.
- Yetki hatası alıyorsanız: rolünüz ilgili işlem için yeterli olmayabilir.
