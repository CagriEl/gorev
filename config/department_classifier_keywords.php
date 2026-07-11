<?php

/**
 * Düşük güvenli BERT tahminlerinde kullanılan anahtar kelime kuralları.
 * Metin küçük harfe çevrilir; Türkçe karakterler korunur.
 */
return [
    'TEMIZLIK_ISLERI' => [
        'çöp', 'çöpler', 'konteyner', 'temizlik', 'süpür', 'süpürme', 'atık', 'pislik',
        'koku', 'kokusu', 'çöp kutusu', 'çöp kamyonu', 'moloz', 'evsel atık', 'geri dönüşüm',
        'kırıntı', 'sokak temizliği', 'kirli', 'leş', 'dolmuş', 'taşıyor',
    ],
    'FEN_ISLERI' => [
        'kaldırım', 'çukur', 'yol', 'asfalt', 'sokak lambası', 'aydınlatma', 'lamba',
        'boru patla', 'su baskını', 'kanalizasyon', 'bordür', 'kaldırım taşı',
        'trafik levhası', 'yaya geçidi', 'parke', 'istinat', 'altyapı', 'menfez',
    ],
    'PARK_BAHCE' => [
        'park', 'bahçe', 'ağaç', 'budama', 'çimen', 'yeşil alan', 'salıncak',
        'oyun alanı', 'çiçek', 'fidan', 'bitki', 'gölge', 'bank',
    ],
    'VETERINER' => [
        'köpek', 'kedi', 'hayvan', 'yaralı', 'sokak hayvan', 'zehirlenme', 'ısırma',
        'kus', 'kuş', 'ölü hayvan', 'barınak',
    ],
];
