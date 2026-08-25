---
paths:
  - 'app/Http/**,app/Services/**,resources/js/**'
---

# Js

## Kepemilikan dompet dan idempotensi mutasi saldo
Dompet pribadi hanya boleh dipakai sebagai sumber dana atau dikelola oleh pemiliknya; dompet bersama dapat dipakai kedua anggota. Semua aksi baru yang mengubah saldo lewat form mobile harus membawa client_reference unik dan ditangani idempoten agar retry/double-tap tidak menggandakan mutasi.
