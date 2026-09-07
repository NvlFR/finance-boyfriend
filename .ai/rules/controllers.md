---
paths:
  - 'app/Http/Controllers/SavingsGoalController.php,app/Models/SavingsGoal.php,app/Policies/SavingsGoalPolicy.php,resources/js/pages/Goals/**,app/Http/Controllers/DashboardController.php'
---

# Controllers

## Kepemilikan tabungan pribadi dan bersama
Savings goal lama dimigrasikan sebagai shared; goal baru default personal bila client tidak mengirim scope. Goal personal hanya boleh disetor, diedit, atau dihapus pembuatnya dan hanya boleh memotong dompet pribadi miliknya. Goal shared dapat dikelola kedua anggota dan dapat memakai dompet pribadi pelaku atau dompet bersama. Dashboard mengatribusikan nilai personal ke pemilik dan nilai shared ke kekayaan bersama.
