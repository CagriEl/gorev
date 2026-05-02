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
- Konum için adres veya enlem/boylam girin.
- Gerekirse haritadan konum seçin.

## 3.2 Görev Güncelleme

- Durum, atanan personel, zaman ve açıklama alanlarını güncelleyebilirsiniz.
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
