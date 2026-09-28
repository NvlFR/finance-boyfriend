---
paths:
  - deploy-production.sh
---

# General

## Gagal deploy harus tetap maintenance
Aktifkan maintenance sebelum backup database dan sinkronisasi source. Jangan menjalankan artisan up dari error trap; kegagalan sinkronisasi, finalisasi, atau health check harus mempertahankan/mengaktifkan maintenance. Pengujian script wajib memakai stub SSH, bukan server production.
