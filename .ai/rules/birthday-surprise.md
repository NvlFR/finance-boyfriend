---
paths:
  - 'app/Models/CoupleSpace.php,app/Policies/BirthdaySurprisePolicy.php,app/Http/Controllers/BirthdaySurpriseController.php,app/Http/Controllers/CoupleSpaceController.php,app/Services/BirthdaySurpriseService.php,resources/js/pages/BirthdaySurprise/**'
---

# Birthday Surprise

## Manager surprise ditetapkan eksplisit per Couple Space
Gunakan couple_spaces.birthday_surprise_manager_user_id untuk menentukan satu anggota yang boleh mengatur surprise; jangan menyimpulkan manager dari user_one/user_two atau nama/gender. Jika kolom belum ditetapkan, fallback ke user_one untuk kompatibilitas. Penerima selalu pasangan dari manager dan tidak boleh melihat halaman pengaturan.
