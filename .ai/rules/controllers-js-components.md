---
paths:
  - 'app/Services/TransactionService.php,app/Http/Requests/Transaction/**,app/Http/Controllers/DashboardController.php,resources/js/components/TransactionDrawer.vue'
---

# Controllers Js Components

## Transfer dan biaya admin harus direkonsiliasi terpisah
Transfer antar-dompet bukan pengeluaran: dompet tujuan menerima `amount`, dompet asal berkurang `amount + fee_amount`. Hanya `fee_amount` yang masuk total uang keluar/tren, dan seluruh operasi create/update/delete harus tetap idempoten serta memakai perhitungan desimal presisi.
