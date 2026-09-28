---
paths:
  - 'resources/js/pages/Transactions/**,resources/js/lib/dates.ts,app/Http/Controllers/TransactionController.php'
---

# Http Controllers

## Tanggal WIB dan daftar riwayat kumulatif
Gunakan jakartaDateKey untuk pengelompokan dan tanggal edit, bukan slice tanggal UTC. Setelah Muat lainnya, request cumulative mengembalikan halaman awal sampai halaman aktif agar reload sesudah mutasi tetap segar dan tidak kehilangan daftar sebelumnya. Filter transaksi, investasi, dan setoran harus konsisten; jenis transfer mencakup mutasi aset, sedangkan income/expense hanya transaksi biasa.
