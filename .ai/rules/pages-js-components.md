---
paths:
  - 'app/Http/Controllers/DashboardController.php,resources/js/pages/Dashboard.vue,resources/js/components/*Chart.vue'
---

# Pages Js Components

## Filter periode hanya untuk grafik dashboard
Filter chart dashboard mendukung 7d, 30d, dan month (bulan berjalan sampai hari ini). Pilihan ini memperbarui tren cashflow, kategori, total, dan rasio scope chart; kartu ringkasan harian/bulanan tetap memakai periodenya sendiri. Biaya transfer dihitung sebagai pengeluaran pribadi, sedangkan pokok transfer tidak masuk cashflow.
