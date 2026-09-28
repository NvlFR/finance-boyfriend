---
paths:
  - 'app/Services/TransactionService.php,app/Http/Controllers/WalletController.php,resources/js/pages/Wallets/**'
---

# Wallets

## Mutasi saldo harus membaca state terbaru
Edit/hapus transaksi wajib memuat ulang dan mengunci baris transaksi di dalam DB transaction; jangan membalik saldo dari route-bound model yang bisa kedaluwarsa. Edit memakai selisih dampak per dompet agar perubahan metadata tidak membutuhkan saldo lama. Edit detail dompet tidak mengirim balance; penyesuaian eksplisit membawa expected_balance dan ditolak bila saldo terkunci sudah berubah.
