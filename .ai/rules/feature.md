---
paths:
  - 'app/Http/Requests/Investment/**,app/Services/InvestmentService.php,resources/js/pages/Investments/**,tests/Feature/InvestmentFeatureTest.php'
---

# Feature

## Pembelian investasi menerima nominal Rupiah
Form beli default ke mode nominal Rupiah tetapi tetap menyediakan mode jumlah unit. Dalam mode Rupiah, server menetapkan gross_amount persis dari nominal input lalu menghitung quantity hingga 8 desimal; saldo berkurang gross_amount + fee. Penjualan tetap wajib berdasarkan quantity agar tidak melebihi unit tersedia.
