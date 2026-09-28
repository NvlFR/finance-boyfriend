---
paths:
  - 'app/Http/Controllers/WalletController.php,app/Http/Controllers/InvestmentController.php,app/Services/TransactionService.php'
---

# Controllers Services

## Arsip aset tidak menghapus buku transaksi
Dompet hanya boleh diarsipkan saat saldo nol dan investasi saat unit nol; jangan hard-delete karena relasi riwayat/biaya harus bertahan. Refund ke dompet terarsip wajib mengaktifkan kembali dompet agar uang terlihat. Transaksi pelunasan serta split yang sudah settled tidak boleh diedit/dihapus tanpa alur pembatalan pelunasan khusus.
