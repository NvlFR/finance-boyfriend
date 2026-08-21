# 📱 Mobile UI/UX Design System & Experience Blueprint

Aesthetic, tactile, and thumb-friendly mobile design for **Couple Finance** (Vue 3 + Tailwind CSS + Shadcn Vue).

---

## 🧭 1. Mobile Navigation Architecture

### A. Bottom Navigation Bar (Floating Island Style)
* **Design:** Floating dock bar di bagian bawah layar dengan `backdrop-blur-md`, rounded-full, dan shadow halus.
* **Menu Items (5 Items):**
  1. 🏠 **Home / Dashboard:** Net worth, quick glance saldo, recent activities.
  2. 💳 **Wallets & Split:** Rincian dompet (His, Her, Joint) & dashboard hutang/talangan kencan.
  3. ➕ **Quick Add (Center Hero Button):** Tombol bulat aksen gradien di tengah untuk modal input transaksi kilat.
  4. 🎯 **Goals & Events:** Tabungan bersama, Wishlist, dan Wedding/Event Planner.
  5. 📊 **Recap & Profile:** Grafik cashflow, Couple Recap, status streak & settings.

---

## ⚡ 2. Frictionless Input UX (Under 5 Seconds)

### A. Bottom Sheet Modal (Drawer)
* Tidak menggunakan modal popup tengah layar yang kaku, melainkan **Swipeable Bottom Sheet Drawer** yang mudah dioperasikan 1 tangan.
* **Custom Number Keypad:** Numpad besar dengan shortcut nominal instan (+10k, +50k, +100k, +500k).

### B. Segmented Control "Who Paid & Split"
* Toggle button visual 1-tap:
  * 🙋‍♂️ **"Paid by Him"**
  * 🙋‍♀️ **"Paid by Her"**
  * 💑 **"Joint Wallet"**
* Split Selector sederhana:
  * `[ 50:50 ]` `[ 100% Mine ]` `[ 100% Partner ]` `[ Custom % ]`

---

## 🎨 3. Visual Identity & Theme (Warm Modern Aesthetic)

### A. Color Palette
* **Dark Mode (Default Modern):**
  * Background: Zinc Deep (`bg-zinc-950` / `bg-zinc-900`)
  * Card Surfaces: `bg-zinc-900/80` with border `border-zinc-800`
* **Accent Colors (Dual Personality):**
  * 💙 **His Accent:** Indigo / Ocean Blue (`#6366F1`)
  * 🌸 **Her Accent:** Rose / Coral Peach (`#F43F5E`)
  * ✨ **Joint Accent:** Emerald / Warm Gold (`#10B981` / `#F59E0B`)

### B. Typography & Micro-animations
* Font: **Plus Jakarta Sans** / **Inter** (Clean, highly readable on small screens).
* Haptic feedback-like micro transitions: smooth tab changes, confetti upon reaching savings goal, bouncy wallet cards.

---

## 📲 4. Screen-by-Screen Mobile Wireframe Breakdown

### 1. Dashboard (Home)
* **Top Header:** Avatar berdua saling menempel dengan badge status streak (🔥 14 Hari).
* **Hero Combined Balance Card:** Total Uang Berdua + Quick Switch Toggle ("Semua", "Punya Gua", "Punya Dia").
* **Smart Settlement Banner (Conditional):** Muncul kartu kecil jika ada saldo talangan (e.g., *"Pacar berhutang Rp 150rb"* -> tombol `[Settle Up]`).
* **Recent Activity Feed:** List transaksi terbaru lengkap dengan avatar siapa yang bayar, tag kategori, dan icon emoji.

### 2. Wallet & Settlement Screen
* Carousel kartu ATM/e-Wallet interaktif horizontal yang bisa di-swipe.
* Ringkasan saldo per dompet + tombol transfer antar dompet kilat.

### 3. Wedding & Big Event Budget Screen
* Timeline progres persiapan acara.
* Card ringkasan total estimasi vs DP terbayar vs sisa pelunasan vendor.
* Checklist vendor berkategori (Venue, Catering, MUA, Photo) dengan status badge `[Lunas]`, `[DP]`, `[Belum]`.

### 4. Goals & Shared Wishlist
* Progress bar tabungan dengan foto cover impian (e.g., foto Gunung Fuji untuk tabungan liburan).
* Wishlist card dengan toggle prioritas & link belanja.
