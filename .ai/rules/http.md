---
paths:
  - 'app/Http/**'
---

# Http

## Kepemilikan dompet pribadi
Dompet pribadi hanya boleh dikelola dan dipakai sebagai sumber mutasi saldo oleh pemiliknya. Kedua anggota tetap boleh memakai dompet bertipe joint. Terapkan validasi ini di request/controller dan ulangi pengecekan pada query lockForUpdate.
