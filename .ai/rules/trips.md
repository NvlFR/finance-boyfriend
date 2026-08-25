---
paths:
  - 'resources/js/pages/Trips/**'
---

# Trips

## Tracking perjalanan harus memakai request terautentikasi
Kirim pembaruan GPS dan push subscription melalui client Inertia/useHttp agar cookie XSRF dipasang otomatis; jangan mengandalkan meta csrf-token yang tidak ada di root view. Poll feed pasangan tanpa overlap, dan hentikan geolocation saat trip selesai atau komponen dilepas.
