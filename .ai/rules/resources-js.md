---
paths:
  - 'resources/js/**'
---

# Resources Js

## Semua nominal Rupiah memakai CurrencyInput
Kolom uang wajib memakai komponen CurrencyInput agar ribuan diformat langsung dengan titik, nilai mentah tetap dikirim tanpa separator, keyboard numerik digunakan, dan spinner native type=number tidak muncul. Input jumlah unit non-Rupiah boleh tetap berupa teks desimal.

## Tanggal kalender browser mengikuti Jakarta
Untuk default tanggal kalender harian, gunakan jakartaDateKey() dari resources/js/lib/dates.ts. Jangan memakai new Date().toISOString().slice(0, 10) karena dapat mundur satu hari pada pagi WIB.
