---
paths:
  - 'app/Http/Controllers/BirthdaySurpriseController.php,app/Services/BirthdaySurpriseService.php,resources/js/pages/BirthdaySurprise/**,resources/js/components/BirthdaySurprise.vue'
---

# Components

## Surprise dikelola manager rahasia selama tujuh hari
Hanya manager yang ditetapkan pada Couple Space yang boleh melihat dan mengubah pengaturan surprise; fallback kompatibilitasnya adalah user_one. Nama pengirim/penerima selalu berasal dari profil database. Input jadwal diperlakukan sebagai Asia/Jakarta, disimpan UTC, dan ends_at wajib tepat 7 hari setelah starts_at; payload rahasia hanya dibagikan ke recipient selama jendela aktif.
