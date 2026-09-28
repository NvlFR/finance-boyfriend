---
paths:
  - resources/js/components/BirthdaySurprise.vue
  - resources/js/components/FeatureTour.vue
---

# Js Components

## Surprise otomatis sekali per hari Jakarta
Selama periode aktif, modal surprise otomatis terbuka saat penerima pertama kali membuka dashboard pada setiap hari kalender Asia/Jakarta. Simpan tanggal terakhir di localStorage agar navigasi atau membuka ulang app pada hari yang sama tidak memicu ulang; banner tetap dapat membuka modal secara manual.

## Feature tour tidak menambah browser history
Perpindahan antarhalaman feature tour harus memakai Inertia visit dengan replace: true. Dialog tur wajib memakai useAccessibleDialog agar fokus terkurung, Escape berfungsi, dan fokus dikembalikan saat tur selesai.
