<script setup lang="ts">
import { onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Heart,
    Sparkles,
    ShieldCheck,
    WalletCards,
    ArrowRightLeft,
    Target,
    Repeat,
    PieChart,
    CheckCircle2,
    ArrowRight,
    Lock,
    Smartphone,
} from '@lucide/vue';

onMounted(() => {
    // If the app is launched from Home Screen as an installed PWA (standalone mode),
    // skip the landing page and redirect straight to login / app dashboard!
    const isStandalone =
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as any).standalone === true;

    if (isStandalone) {
        router.visit('/login');
    }
});
</script>

<template>
    <Head title="Couple Finance - Kelola Keuangan Pasangan Tanpa Drama">
        <meta name="description" content="Aplikasi manajemen keuangan pasangan. Catat kas bersama, split bill kencan, tabungan impian nikah & liburan berdua secara transparan." />
    </Head>

    <div class="min-h-screen bg-zinc-950 text-zinc-100 selection:bg-rose-500 selection:text-white font-sans antialiased overflow-x-hidden">
        <!-- Ambient Gradient Background Bubbles -->
        <div class="fixed top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-gradient-to-tr from-rose-500/20 via-indigo-500/20 to-purple-500/10 blur-3xl pointer-events-none" />
        <div class="fixed bottom-0 right-0 h-[450px] w-[450px] rounded-full bg-rose-600/10 blur-3xl pointer-events-none" />

        <!-- Navigation Header -->
        <header class="relative z-20 border-b border-zinc-800/80 bg-zinc-950/80 backdrop-blur-md sticky top-0">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                <!-- Brand Logo -->
                <Link href="/" class="flex items-center gap-2.5 group">
                    <img
                        src="/icons/icon-192.png"
                        alt="Finance Boyfriend Logo"
                        class="h-9 w-9 rounded-2xl object-cover shadow-lg shadow-rose-500/20 group-hover:scale-105 transition-transform"
                    />
                    <span class="text-lg font-black tracking-tight text-white">
                        Finance<span class="text-rose-500">Boyfriend</span>
                    </span>
                </Link>

                <!-- Auth Navigation -->
                <div class="flex items-center gap-3">
                    <Link
                        href="/login"
                        class="rounded-full px-4 py-2 text-xs font-bold text-zinc-300 hover:text-white hover:bg-zinc-900 transition-colors"
                    >
                        Masuk
                    </Link>
                    <Link
                        href="/register"
                        class="rounded-full bg-gradient-to-r from-rose-500 to-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-rose-500/20 hover:opacity-95 transition-all hover:scale-105"
                    >
                        Mulai Berdua
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative z-10 mx-auto max-w-5xl px-4 pt-12 pb-16 sm:px-6 sm:pt-20 sm:pb-24 text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-1.5 text-xs font-bold text-rose-400 backdrop-blur-md mb-6 animate-bounce">
                <Sparkles class="h-4 w-4 text-amber-400" />
                <span>Aplikasi Keuangan Pasangan #1 di Indonesia</span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight text-white leading-tight">
                Kelola Uang Berdua<br class="hidden sm:inline" />
                <span class="bg-gradient-to-r from-rose-400 via-purple-300 to-indigo-400 bg-clip-text text-transparent">
                    Tanpa Drama & Transparan
                </span>
            </h1>

            <!-- Subtitle -->
            <p class="mx-auto mt-5 max-w-2xl text-sm sm:text-base text-zinc-400 leading-relaxed">
                Dari split bill kencan, kas bersama, tabungan nikah & liburan impian, hingga pengingat tagihan rutin. Semua tercatat otomatis & transparan untuk hubungan yang makin sehat.
            </p>

            <!-- Hero Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <Link
                    href="/register"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-rose-500 via-purple-600 to-indigo-600 px-7 py-3.5 text-sm font-extrabold text-white shadow-xl shadow-rose-500/25 transition-all hover:scale-105 hover:opacity-95"
                >
                    <Heart class="h-4 w-4 fill-current" />
                    <span>Mulai Berdua (Gratis)</span>
                    <ArrowRight class="h-4 w-4" />
                </Link>

                <Link
                    href="/login"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-2xl border border-zinc-800 bg-zinc-900 px-7 py-3.5 text-sm font-bold text-zinc-300 hover:bg-zinc-800 hover:text-white transition-colors"
                >
                    <Lock class="h-4 w-4 text-indigo-400" />
                    <span>Masuk ke Akun Kamu</span>
                </Link>
            </div>

            <div class="mt-4 flex items-center justify-center gap-4 text-xs text-zinc-500">
                <span class="flex items-center gap-1.5"><ShieldCheck class="h-4 w-4 text-emerald-400" /> Terenkripsi & Aman</span>
                <span>•</span>
                <span class="flex items-center gap-1.5"><Smartphone class="h-4 w-4 text-indigo-400" /> Siap Digunakan di HP (PWA)</span>
            </div>

            <!-- 3D Couple Mascot & App Showcase Banner -->
            <div class="relative mt-10 flex flex-col items-center">
                <img
                    src="/icons/animate-logo.png"
                    alt="Finance Boyfriend Couple"
                    class="h-64 sm:h-80 w-auto object-contain filter drop-shadow-[0_20px_30px_rgba(244,63,94,0.3)] animate-pulse hover:scale-105 transition-transform duration-300"
                />
            </div>

            <!-- App Interactive Showcase Card Preview -->
            <div class="relative mt-8 overflow-hidden rounded-3xl border border-zinc-800 bg-gradient-to-b from-zinc-900 to-zinc-950 p-6 sm:p-8 shadow-2xl">
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-rose-500/20 blur-3xl pointer-events-none" />
                <div class="absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none" />

                <!-- Mockup App Header -->
                <div class="relative z-10 flex items-center justify-between border-b border-zinc-800/80 pb-4 mb-6">
                    <div class="flex items-center gap-3">
                        <img src="/icons/icon-192.png" alt="Logo" class="h-10 w-10 rounded-2xl object-cover shadow-md" />
                        <div class="text-left">
                            <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                                Ruang Kencan Rizky & Aulia <Heart class="h-3.5 w-3.5 text-rose-500 fill-current" />
                            </h3>
                            <p class="text-[11px] text-zinc-400">Total Kekayaan Berdua • Rp 18.500.000</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400 border border-emerald-500/20">
                        ● Live Sync
                    </span>
                </div>

                <!-- Showcase Cards Grid -->
                <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-4 space-y-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span>Kas Bersama</span>
                            <WalletCards class="h-4 w-4 text-indigo-400" />
                        </div>
                        <p class="text-xl font-extrabold text-white">Rp 5.200.000</p>
                        <p class="text-[10px] text-emerald-400">BCA Kas Kencan Berdua</p>
                    </div>

                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-4 space-y-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span>Split Bill Kencan</span>
                            <ArrowRightLeft class="h-4 w-4 text-rose-400" />
                        </div>
                        <p class="text-xl font-extrabold text-white">Rp 125.000</p>
                        <p class="text-[10px] text-rose-400">Rizky menalangi Dinner Seafood</p>
                    </div>

                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/90 p-4 space-y-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span>Tabungan Nikah</span>
                            <Target class="h-4 w-4 text-amber-400" />
                        </div>
                        <p class="text-xl font-extrabold text-white">65% Terkumpul</p>
                        <div class="h-1.5 w-full rounded-full bg-zinc-800">
                            <div class="h-full rounded-full bg-amber-400" style="width: 65%" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="relative z-10 border-t border-zinc-800/80 bg-zinc-900/50 py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 text-center">
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Fitur Lengkap untuk Pasangan Bahagia
                </h2>
                <p class="mt-3 text-sm text-zinc-400 max-w-xl mx-auto">
                    Didesain khusus untuk memenuhi semua kebutuhan finansial hubungan kalian.
                </p>

                <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-left">
                    <!-- Feature 1 -->
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-950 p-6 space-y-3 hover:border-rose-500/40 transition-colors">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            <WalletCards class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-bold text-white">Dompet & Kas Bersama</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Pisahkan dompet pribadi dan kas bersama. Pantau saldo BCA, Mandiri, GoPay, OVO, hingga Cash dalam satu tempat.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-950 p-6 space-y-3 hover:border-indigo-500/40 transition-colors">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <ArrowRightLeft class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-bold text-white">Split Bill & Talangan Kencan</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Bagi 50:50 atau talangan otomatis setiap kali ngedate, nonton, atau belanja bareng.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-950 p-6 space-y-3 hover:border-amber-500/40 transition-colors">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <Target class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-bold text-white">Target Tabungan Impian</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Rencanakan liburan ke Bali, dana pernikahan, atau DP rumah dengan progres tabungan yang terukur.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-950 p-6 space-y-3 hover:border-purple-500/40 transition-colors">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                            <Repeat class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-bold text-white">Langganan Rutin & Reminder</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Pengingat tagihan Netflix, Spotify, iCloud, atau cicilan bersama agar tidak pernah telat bayar.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-950 p-6 space-y-3 hover:border-emerald-500/40 transition-colors">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <PieChart class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-bold text-white">Grafik & Donat Analytics</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Visualisasi tren pengeluaran 7 hari dan diagram donat per kategori pengeluaran kencan vs pribadi.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="rounded-3xl border border-zinc-800 bg-zinc-950 p-6 space-y-3 hover:border-rose-500/40 transition-colors">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-bold text-white">Settlement Utang 1-Klik</h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Lunaskan talangan antar pasangan dengan 1 kali tap tanpa perlu menghitung manual di kalkulator.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Bottom Banner -->
        <section class="relative z-10 mx-auto max-w-5xl px-4 py-16 sm:px-6 sm:py-24 text-center">
            <div class="relative overflow-hidden rounded-3xl border border-rose-500/30 bg-gradient-to-br from-indigo-950 via-zinc-900 to-rose-950 p-8 sm:p-12 shadow-2xl">
                <div class="relative z-10 space-y-4">
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                        Siap Mulai Perjalanan Keuangan Romantis Berdua?
                    </h2>
                    <p class="text-xs sm:text-sm text-zinc-300 max-w-xl mx-auto">
                        Daftar gratis sekarang dan bangun transparansi finansial demi masa depan hubungan yang lebih bahagia.
                    </p>
                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <Link
                            href="/register"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-rose-500 to-indigo-600 px-8 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-rose-500/25 hover:scale-105 transition-transform"
                        >
                            <Heart class="h-4 w-4 fill-current" /> Daftar Bebas Drama
                        </Link>
                        <Link
                            href="/login"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-8 py-3.5 text-sm font-bold text-white backdrop-blur-md hover:bg-white/20 transition-colors"
                        >
                            Masuk Akun
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-zinc-900 bg-zinc-950 py-8 text-center text-xs text-zinc-500">
            <div class="mx-auto max-w-6xl px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 font-bold text-white">
                    <img src="/icons/icon-192.png" alt="Logo" class="h-5 w-5 rounded-lg object-cover" />
                    <span>Finance<span class="text-rose-500">Boyfriend</span></span>
                </div>
                <p>&copy; {{ new Date().getFullYear() }} Finance Boyfriend. Dibuat khusus untuk keuangan pasangan Indonesia.</p>
            </div>
        </footer>
    </div>
</template>
