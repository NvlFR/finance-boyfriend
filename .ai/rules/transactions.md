---
paths:
  - 'resources/js/layouts/AppLayout.vue,resources/js/components/TransactionDrawer.vue,resources/js/pages/Transactions/**'
---

# Transactions

## Navigasi history wajib memuat ulang data finansial
Setelah mutasi transaksi, gunakan replace history. AppLayout menutup drawer saat popstate dan memanggil router.reload() setelah event navigate agar Back/Forward tidak menampilkan snapshot saldo atau transaksi lama dari history Inertia.
