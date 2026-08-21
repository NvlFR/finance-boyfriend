# 💑 Couple Finance - Feature Specification & Product Blueprint

A modern, transparent, and collaborative personal & couple financial management web app built with **Laravel 12**, **Inertia.js v3**, and **Vue 3 (Tailwind CSS + Shadcn Vue)**.

---

## 🎯 1. Product Vision & Core Philosophy

* **Full Transparency & Trust:** Pasangan bisa saling melihat cash flow, dompet, dan kebiasaan finansial masing-masing secara transparan tanpa ada yang disembunyikan.
* **Frictionless Daily Tracking:** Pencatatan transaksi harus sangat cepat (< 5 detik) dan mobile-friendly.
* **Collaborative Goals & Romance:** Membantu pasangan mencapai financial goals bersama (kencan, liburan, tabungan nikah/rumah) sekaligus merekam kenangan kencan manis.
* **Warm & Aesthetic UI/UX:** Tampilan modern, clean, elegan dengan sentuhan personal & warm (bukan corporate banking yang kaku).

---

## 👥 2. User & Pairing Model

1. **Individual Authentication & Pairing:**
   * Masing-masing login menggunakan akun sendiri (email/password).
   * **Pairing Mechanism:** User A meng-generate *Invite Code / Link QR*, User B memasukkan kode tersebut untuk menghubungkan (*couple pairing*).
   * Setelah dipasangkan (*paired*), akun mereka tergabung dalam satu ruang kerja finansial bersama (*Couple Space*).
2. **User Profiles:**
   * Avatar, nickname, warna tema personal (misal: Biru/Indigo untuk Pria & Rose/Peach untuk Wanita).

---

## 💰 3. Key Feature Modules

### A. Multi-Account / Wallet Management
* **Tipe Dompet:**
  * **His Wallets:** (e.g., BCA, Mandiri, Jenius, Cash)
  * **Her Wallets:** (e.g., BCA, GoPay, ShopeePay, Seabank)
  * **Joint Wallets (Kas Bersama):** Dompet yang di-fund bersama (e.g., Rekening Bersama, Amplop Kencan).
* **Fitur Dompet:**
  * Real-time balance tracker.
  * Transfer antar dompet (internal transfer & cross-partner transfer).
  * Total Net Worth gabungan & individual breakdown.

---

### B. Smart Transaction & Split Expense (Catat Transaksi)
* **Kategori Transaksi:**
  * Pemasukan (Gaji, Bonus, Freelance, Investasi).
  * Pengeluaran:
    * **Personal Expense** (Kebutuhan pribadi masing-masing).
    * **Date / Shared Expense** (Makan bareng, nonton, bensin/tol, kado, belanja bulanan).
* **Split Bill & Settlement Engine:**
  * Mode Pembayaran:
    * *Paid by Me / Paid by Partner* sepenuhnya.
    * *Split 50:50* atau *Custom Split Ratio / Custom Amount*.
    * *Paid from Joint Wallet* (Kas bersama).
  * **Settlement Dashboard:**
    * Ringkasan "Siapa yang menalangi siapa" secara otomatis (misal: *"Pacar kamu berhutang Rp 125.000 ke kamu"*).
    * Fitur **"Settle Up / Lunaskan"** dalam 1 klik.
* **Attachment, OCR & Memory:**
  * **Smart Receipt OCR (AI Scan Struk):** Foto struk resto/belanja, otomatis isi nominal dan nama merchant.
  * **Couple Dating Journal:** Upload foto momen kencan + catatan kenangan/memo cinta (*Love Notes*) di transaksi tersebut.

---

### C. Joint Goals & Savings Pockets (Tabungan Bersama)
* **Tujuan Tabungan:**
  * Target tabungan dengan deadline & nominal (e.g. *Trip ke Jepang 2027: Rp 30.000.000*, *Emergency Fund: Rp 20.000.000*, *Wedding Fund*).
* **Progress Tracking:**
  * Visual progress bar & persentase tercapai.
  * Riwayat setoran: Siapa yang setor berapa dan kapan.
  * Simulasi setoran bulanan yang direkomendasikan.

---

### D. Wedding & Big Event Budget Planner (Event / Proyek Bersama)
* **Dedicated Project Budgeting:**
  * Ruang khusus untuk mengelola anggaran acara besar (e.g. *Lamaran, Pernikahan, Renovasi Rumah, Liburan Luar Negeri*).
* **Vendor & Expense Checklist:**
  * Breakdown per kategori vendor (Venue, Catering, MUA, Dokumentasi, Busana, Undangan, Souvenir).
  * Status Pembayaran: *Estimated vs Actual Cost*, *DP (Down Payment)*, *Sisa Pelunasan*, dan *Jatuh Tempo*.
* **Visual Progress & Summary:**
  * Total anggaran yang sudah terbayar vs sisa kewajiban pelunasan vendor.

---

### E. Wishlist & Secret Surprise Fund (Kado & Rencana Belanja)
* **Shared Wishlist:**
  * Daftar barang/keinginan bersama (e.g., *Air Fryer, Koper Liburan, Smart TV*).
  * Prioritas barang (High, Medium, Low) & link e-commerce.
* **Secret Surprise Fund (Mode Kado Ultah/Anniversary):**
  * Dompet atau kantong tabungan tersembunyi yang nominal/deskripsinya di-*blur* atau dikunci PIN/Countdown hingga tanggal ultah/anniversary pasangan tiba, agar kado kejutan tidak bocor!

---

### F. Recurring Bills & Subscription Splitter (Langganan Bareng)
* **Shared Subscriptions Tracker:**
  * Kelola langganan bersama (Netflix, Spotify Family, YouTube Premium, iCloud, Wi-Fi).
  * Jadwal siklus tagihan (Bulanan / Tahunan) & auto-reminder jatuh tempo.
  * Giliran bayar (*Who pays next* atau auto split 50:50).

---

### G. Monthly Budgets & Spending Limits
* **Budget per Kategori:**
  * Misal budget *"Date Night & Makan Luar"* = Rp 2.500.000 / bulan.
  * Misal budget *"Self Reward"* masing-masing = Rp 1.000.000 / bulan.
* **Visual Status:**
  * Safe (Hijau), Warning > 80% (Kuning), Overbudget > 100% (Merah).
  * Sisa budget harian yang disarankan agar tidak boncos sebelum akhir bulan.

---

### H. Gamification & Streak Tracker
* **Tracking Streak:** Penghitung berapa hari berturut-turut kalian berdua rajin mencatat pengeluaran.
* **Couple Milestone Badges:** Badge prestasi finansial lucu (e.g. *"First 10 Juta Saved"*, *"Date Night Master"*, *"Budget Hero"*).

---

### I. Analytics & Monthly Couple Recap
* **Cashflow Analytics:**
  * Visual grafik perbandingan Pemasukan vs Pengeluaran bulanan.
  * Breakdown kategori terboros bulan ini (Pie Chart / Donut Chart).
  * Rasio kontribusi pengeluaran bersama (Siapa yang lebih sering nalangin).
* **"Couple Financial Recap" (Monthly Summary):**
  * Statistik seru ala *Spotify Wrapped*: Total date night, tempat makan paling favorit, total uang yang berhasil ditabung bareng.

---

## 🎨 4. UI/UX Design System & Experience Principles

1. **Visual Style:**
   * Modern minimalis (Clean borders, subtle glassmorphism, rounded-2xl cards).
   * **Color Palette:** Neutral zinc/slate base dengan dual accent color yang harmonis (Indigo/Violet + Rose/Emerald).
2. **Speed & Ergonomics:**
   * **Quick Add Button:** Floating Action Button (FAB) atau shortcut keyboard `(N)` untuk input transaksi kilat.
   * **Mobile-First Layout:** Navigasi bawah (*Bottom Navigation Bar*) di mobile + collapsible sidebar di desktop.
3. **Micro-interactions:**
   * Animasi progress bar tabungan yang smooth saat ada setoran baru.
   * Efek konfeti animasi ketika Joint Goal berhasil tercapai 100%.

---

## 🗺️ 5. Implementation Roadmap (Phases)

### Phase 1: MVP Core (Fondasi & Transaksi Harian)
- [ ] User Auth + Couple Pairing (Invite Code System).
- [ ] Multi-Wallet Management (His, Her, Joint Wallets).
- [ ] Fast Transaction Input (Income, Expense, Transfer).
- [ ] Split Bill & Settlement Tracker (Siapa talangin siapa + Settle Up).
- [ ] Dashboard Ringkasan Saldo & Riwayat Transaksi Terbaru.

### Phase 2: Goals, Wishlist, Subscriptions & Event Planner
- [ ] Shared Savings Goals (Kantong Tabungan Bersama).
- [ ] Wedding & Big Event Budget Planner (Vendor Checklist, DP & Pelunasan).
- [ ] Shared Wishlist & Secret Surprise Fund.
- [ ] Recurring Bills & Subscription Splitter.
- [ ] Monthly Category Budgets & Overspending Alerts.
- [ ] Moment Photo Upload & Love Notes.

### Phase 3: Smart Tools & Analytics
- [ ] Visual Charts & Analytics (Cashflow & Spending Categories).
- [ ] Smart Receipt OCR Scan.
- [ ] Gamification Badges & Logging Streak.
- [ ] Monthly Couple Recap / Wrapped.
- [ ] Export Report (PDF / Excel).
