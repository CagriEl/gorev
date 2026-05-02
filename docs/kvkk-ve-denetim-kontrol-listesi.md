# KVKK ve Denetim Kontrol Listesi

## Veri Sınıflandırma

- Kişisel veri: `users.name`, `users.email`, `departments.*_phone`.
- Operasyonel kişisel bağlam: görev ataması, saha konumları, audit kaydındaki kullanıcı kimliği.
- Hassas işlem verisi: rol değişimi, müdürlük sorumluluk değişimi, kapanış onayları.

## Teknik Kontroller

- Role göre maskeleme:
  - E-posta ve telefon bilgilerinde yetkisiz rollere kısmi maskeleme.
- Denetim kapsamı:
  - Görev, kullanıcı, müdürlük ve onay süreci değişiklikleri.
  - Her kayıtta `who/when/what` ve `request_context` bulunmalı.
- Silme politikası:
  - Fiziksel silme kapalı, yumuşak silme + geri alma + denetim kaydı.

## Saklama ve Arşiv

- Uygulama denetim kayıtları en az 365 gün saklanır.
- Arşiv stratejisi: periyodik dışa aktarma + güvenli depolama.
- Silme ve anonimleştirme kararları kurumun resmi veri saklama politikasıyla uyumlu olmalıdır.

## Denetim Sorgulama Gereksinimleri

- Filtreler: tarih aralığı, işlem yapan kullanıcı, varlık tipi, işlem tipi.
- Raporlama: CSV dışa aktarma (sadece yetkili roller).
- İnceleme rolleri: Admin ve ViceMayor.
