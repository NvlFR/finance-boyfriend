---
paths:
  - 'app/Models/Investment*.php,app/Services/InvestmentService.php,app/Http/Controllers/InvestmentController.php,app/Http/Requests/Investment/**,resources/js/pages/Investments/**,app/Http/Controllers/DashboardController.php,app/Services/TransactionReportService.php'
---

# Services

## Investasi adalah perpindahan aset, bukan pengeluaran
Pembelian investasi mengurangi dompet sebesar nilai beli + biaya dan menambah nilai portofolio; penjualan menambah dompet sebesar nilai jual - biaya. Pokok beli/jual tidak masuk arus kas pengeluaran/pemasukan harian. Semua trade wajib presisi desimal, lockForUpdate, client_reference idempoten, serta membatasi dompet pribadi dan aset pribadi ke pemiliknya.
