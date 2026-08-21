<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Heart,
    Plus,
    Handshake,
    WalletCards,
    TrendingDown,
    TrendingUp,
    Sparkles,
    ArrowUpRight,
    ArrowDownLeft,
    ArrowRightLeft,
    CheckCircle2,
    Users,
    Target,
    Gift,
    Repeat,
    PieChart,
    Tag,
} from '@lucide/vue';
import CoupleHeader from '@/components/CoupleHeader.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import TransactionDrawer from '@/components/TransactionDrawer.vue';
import WalletCard from '@/components/WalletCard.vue';
import CashflowChart from '@/components/CashflowChart.vue';
import CategoryDonutChart from '@/components/CategoryDonutChart.vue';
import type { CoupleSpace, Wallet, Category, Transaction } from '@/types/finance';
import type { User } from '@/types/auth';

import { useTransactionModal } from '@/composables/useTransactionModal';

const props = withDefaults(
    defineProps<{
        hasCoupleSpace: boolean;
        coupleSpace?: CoupleSpace | null;
        partner?: User | null;
        wallets?: Wallet[];
        userWallets?: Wallet[];
        partnerWallets?: Wallet[];
        jointWallets?: Wallet[];
        totalNetWorth?: number;
        userNetWorth?: number;
        partnerNetWorth?: number;
        jointNetWorth?: number;
        recentTransactions?: Transaction[];
        settlementDebt?: {
            net_balance: number;
            debtor_id: number | null;
            creditor_id: number | null;
            debtor_name: string | null;
            creditor_name: string | null;
            amount_owed: number;
            user_one_balance: number;
            user_two_balance: number;
            unsettled_splits_count: number;
        } | null;
        monthlySpending?: number;
        monthlyIncome?: number;
        categories?: Category[];
        dailyTrend?: Array<{
            date: string;
            day: string;
            expense: number;
            income: number;
        }>;
        categorySpending?: Array<{
            id: number;
            name: string;
            color: string;
            total: number;
            percentage: number;
        }>;
        spendingByScope?: {
            shared: number;
            personal: number;
        };
        upcomingSubscriptions?: any[];
        auth: {
            user: User;
        };
    }>(),
    {
        hasCoupleSpace: false,
        wallets: () => [],
        userWallets: () => [],
        partnerWallets: () => [],
        jointWallets: () => [],
        totalNetWorth: 0,
        userNetWorth: 0,
        partnerNetWorth: 0,
        jointNetWorth: 0,
        recentTransactions: () => [],
        monthlySpending: 0,
        monthlyIncome: 0,
        categories: () => [],
        dailyTrend: () => [],
        categorySpending: () => [],
        upcomingSubscriptions: () => [],
    }
);

const activeTab = ref<'all' | 'mine' | 'partner' | 'joint'>('all');
const { openModal } = useTransactionModal();

const displayedWallets = computed(() => {
    switch (activeTab.value) {
        case 'mine':
            return props.userWallets || [];
        case 'partner':
            return props.partnerWallets || [];
        case 'joint':
            return props.jointWallets || [];
        default:
            return props.wallets || [];
    }
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour >= 4 && hour < 11) return 'Selamat Pagi';
    if (hour >= 11 && hour < 15) return 'Selamat Siang';
    if (hour >= 15 && hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const todayDateFormatted = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const formattedTotalNetWorth = computed(() => {
    return 'Rp ' + Number(props.totalNetWorth).toLocaleString('id-ID');
});

const formattedMonthlySpending = computed(() => {
    return 'Rp ' + Number(props.monthlySpending).toLocaleString('id-ID');
});

const formattedMonthlyIncome = computed(() => {
    return 'Rp ' + Number(props.monthlyIncome).toLocaleString('id-ID');
});

function formatCurrency(amount: number) {
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

// Quick 1-click Settle Up
const settleForm = useForm({
    amount: props.settlementDebt?.amount_owed || 0,
    to_user_id: props.settlementDebt?.creditor_id || 0,
    payment_method: 'Transfer Bank',
    notes: 'Pelunasan talangan kencan',
});

function handleQuickSettle() {
    if (!props.settlementDebt || props.settlementDebt.amount_owed <= 0) return;
    settleForm.amount = props.settlementDebt.amount_owed;
    settleForm.to_user_id = props.settlementDebt.creditor_id || 0;
    settleForm.post('/settlements', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Dashboard - Couple Finance" />

    <div class="space-y-6">
        <!-- 💖 Welcome Greeting Headline -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ greeting }}, {{ auth.user.nickname || auth.user.name.split(' ')[0] }}! 👋
                    </h1>
                    <span
                        v-if="partner"
                        class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                    >
                        <Heart class="h-3 w-3 fill-current animate-pulse" /> & {{ partner.nickname || partner.name.split(' ')[0] }}
                    </span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ todayDateFormatted }} • {{ coupleSpace ? coupleSpace.name : 'Kelola keuangan kencan berdua' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    v-if="coupleSpace"
                    href="/couple-space"
                    class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3.5 py-1.5 text-xs font-bold text-zinc-700 shadow-xs hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 transition-all"
                >
                    <Heart class="h-3.5 w-3.5 text-rose-500 fill-current" />
                    <span>{{ partner ? `${partner.nickname || partner.name.split(' ')[0]}` : 'Undang Pasangan' }}</span>
                </Link>
            </div>
        </div>

        <!-- First-Time Onboarding Prompt (If no Couple Space) -->
            <div
                v-if="!hasCoupleSpace"
                class="rounded-3xl border border-rose-200 bg-gradient-to-r from-rose-50 to-indigo-50 p-6 dark:border-rose-900/50 dark:bg-zinc-900"
            >
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <Sparkles class="h-5 w-5 text-rose-500" />
                            Selamat Datang di Couple Finance!
                        </h2>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                            Kelola uang bareng, catat split bill kencan, dan wujudkan tabungan impian berdua secara transparan.
                        </p>
                    </div>
                    <Link
                        href="/couple-space"
                        class="inline-flex items-center gap-2 rounded-2xl bg-rose-500 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-rose-500/20 transition-all hover:bg-rose-600"
                    >
                        <Heart class="h-4 w-4 fill-current" /> Buka Ruang Pasangan
                    </Link>
                </div>
            </div>

            <!-- Hero Net Worth Card -->
            <div
                class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-900/90 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800"
            >
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-rose-500/20 blur-3xl" />
                <div class="absolute -left-10 -bottom-10 h-40 w-40 rounded-full bg-indigo-500/20 blur-3xl" />

                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-zinc-400">
                            Total Kekayaan Berdua (Net Worth)
                        </span>
                        <button
                            type="button"
                            @click="openModal"
                            class="hidden md:inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-md hover:bg-white/20 transition-colors"
                        >
                            <Plus class="h-4 w-4" /> Catat Transaksi
                        </button>
                    </div>

                    <div class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                        {{ formattedTotalNetWorth }}
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-white/10">
                        <div>
                            <span class="text-[11px] text-indigo-300">Punya Kamu</span>
                            <p class="text-xs sm:text-sm font-bold truncate">
                                {{ formatCurrency(userNetWorth) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[11px] text-rose-300">{{ partner ? partner.nickname || partner.name.split(' ')[0] : 'Pasangan' }}</span>
                            <p class="text-xs sm:text-sm font-bold truncate">
                                {{ formatCurrency(partnerNetWorth) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[11px] text-emerald-300">Kas Bersama</span>
                            <p class="text-xs sm:text-sm font-bold truncate">
                                {{ formatCurrency(jointNetWorth) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Subscription Bill Reminder Banner -->
            <div
                v-if="upcomingSubscriptions && upcomingSubscriptions.length > 0"
                class="rounded-3xl border border-amber-500/30 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-4 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-sm">
                            <Repeat class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-amber-900 dark:text-amber-300">
                                Pengingat Tagihan Jatuh Tempo ({{ upcomingSubscriptions.length }})
                            </h3>
                            <p class="text-[11px] text-zinc-600 dark:text-zinc-400 mt-0.5">
                                <strong class="text-zinc-900 dark:text-zinc-100">{{ upcomingSubscriptions[0].name }}</strong>
                                (Rp {{ Number(upcomingSubscriptions[0].amount).toLocaleString('id-ID') }}) jatuh tempo {{ new Date(upcomingSubscriptions[0].next_billing_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}.
                            </p>
                        </div>
                    </div>

                    <Link
                        href="/subscriptions"
                        class="shrink-0 rounded-full bg-amber-500/20 px-3 py-1.5 text-[11px] font-bold text-amber-700 dark:text-amber-300 hover:bg-amber-500/30 transition-colors"
                    >
                        Lihat Tagihan &rarr;
                    </Link>
                </div>
            </div>

            <!-- Quick Couple Features Grid (6 Full Features) -->
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center">
                <!-- Goals -->
                <Link
                    href="/goals"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm hover:border-indigo-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                        <Target class="h-5 w-5" />
                    </div>
                    <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200">Tabungan</span>
                </Link>

                <!-- Wishlist -->
                <Link
                    href="/wishlists"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                        <Gift class="h-5 w-5" />
                    </div>
                    <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200">Wishlist</span>
                </Link>

                <!-- Subscriptions -->
                <Link
                    href="/subscriptions"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm hover:border-amber-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <Repeat class="h-5 w-5" />
                    </div>
                    <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200">Langganan</span>
                </Link>

                <!-- Budgets -->
                <Link
                    href="/budgets"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                        <PieChart class="h-5 w-5" />
                    </div>
                    <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200">Anggaran</span>
                </Link>

                <!-- Categories -->
                <Link
                    href="/categories"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm hover:border-purple-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                        <Tag class="h-5 w-5" />
                    </div>
                    <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200">Kategori</span>
                </Link>

                <!-- Couple Space -->
                <Link
                    href="/couple-space"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm hover:border-pink-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-pink-500/10 text-pink-600 dark:bg-pink-500/20 dark:text-pink-400">
                        <Heart class="h-5 w-5" />
                    </div>
                    <span class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200">Pasangan</span>
                </Link>
            </div>

            <!-- Settlement Alert Banner (If Debt Exists) -->
            <div
                v-if="settlementDebt && settlementDebt.amount_owed > 0"
                class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 shadow-sm dark:border-amber-500/20"
            >
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white">
                            <Handshake class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-amber-900 dark:text-amber-300">
                                {{ settlementDebt.debtor_id === auth.user.id ? 'Kamu Punya Talangan ke Pasangan' : `${settlementDebt.debtor_name} Berhutang ke Kamu` }}
                            </h3>
                            <p class="text-xs text-amber-800/80 dark:text-amber-400">
                                Sebesar <span class="font-bold">Rp {{ Number(settlementDebt.amount_owed).toLocaleString() }}</span> ({{ settlementDebt.unsettled_splits_count }} transaksi kencan)
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button
                            v-if="settlementDebt.debtor_id === auth.user.id"
                            type="button"
                            @click="handleQuickSettle"
                            :disabled="settleForm.processing"
                            class="w-full sm:w-auto rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-600 transition-colors"
                        >
                            Settle Up (Lunaskan)
                        </button>
                        <Link
                            href="/settlements"
                            class="w-full sm:w-auto text-center rounded-xl border border-amber-500/30 px-3 py-2 text-xs font-medium text-amber-900 dark:text-amber-300 hover:bg-amber-500/10 transition-colors"
                        >
                            Lihat Rincian
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Monthly In/Out Cashflow Summary -->
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                        <ArrowDownLeft class="h-4 w-4" />
                        <span class="text-xs font-medium">Pemasukan Bulan Ini</span>
                    </div>
                    <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-zinc-100">
                        {{ formattedMonthlyIncome }}
                    </p>
                </div>
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400">
                        <ArrowUpRight class="h-4 w-4" />
                        <span class="text-xs font-medium">Pengeluaran Bulan Ini</span>
                    </div>
                    <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-zinc-100">
                        {{ formattedMonthlySpending }}
                    </p>
                </div>
            </div>

            <!-- 📊 Interactive Financial Charts (7-Day Cashflow Trend & Monthly Category Donut) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <CashflowChart :data="dailyTrend || []" />
                <CategoryDonutChart
                    :categories="categorySpending || []"
                    :monthly-spending="monthlySpending"
                    :spending-by-scope="spendingByScope"
                />
            </div>

            <!-- Wallets Section -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <WalletCards class="h-4 w-4 text-indigo-500" />
                        <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Dompet</h2>
                    </div>
                    <Link
                        href="/wallets"
                        class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60 transition-colors"
                    >
                        Kelola Semua
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Wallet Filter Tabs -->
                <div class="flex gap-1.5 overflow-x-auto pb-1 text-xs">
                    <button
                        type="button"
                        @click="activeTab = 'all'"
                        class="rounded-full px-3 py-1 font-medium transition-all"
                        :class="activeTab === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'"
                    >
                        Semua ({{ wallets.length }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'mine'"
                        class="rounded-full px-3 py-1 font-medium transition-all"
                        :class="activeTab === 'mine' ? 'bg-indigo-600 text-white' : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'"
                    >
                        Punya Kamu ({{ userWallets.length }})
                    </button>
                    <button
                        v-if="partner"
                        type="button"
                        @click="activeTab = 'partner'"
                        class="rounded-full px-3 py-1 font-medium transition-all"
                        :class="activeTab === 'partner' ? 'bg-rose-600 text-white' : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'"
                    >
                        {{ partner.nickname || partner.name.split(' ')[0] }} ({{ partnerWallets.length }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'joint'"
                        class="rounded-full px-3 py-1 font-medium transition-all"
                        :class="activeTab === 'joint' ? 'bg-emerald-600 text-white' : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'"
                    >
                        Kas Bersama ({{ jointWallets.length }})
                    </button>
                </div>

                <!-- Wallet Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <WalletCard
                        v-for="w in displayedWallets"
                        :key="w.id"
                        :wallet="w"
                        :is-joint="w.type === 'joint'"
                        :is-owner="w.user_id === auth.user.id"
                    />

                    <div
                        v-if="displayedWallets.length === 0"
                        class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-xs text-zinc-500 dark:border-zinc-800"
                    >
                        Belum ada dompet di kategori ini.
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Feed -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Aktivitas Terkini</h2>
                    <Link
                        href="/transactions"
                        class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60 transition-colors"
                    >
                        Lihat Semua
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 bg-white divide-y divide-zinc-100 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:divide-zinc-800/80">
                    <div
                        v-for="tx in recentTransactions"
                        :key="tx.id"
                        class="flex items-center justify-between p-3.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl text-white"
                                :style="{ backgroundColor: tx.category?.color || '#6366F1' }"
                            >
                                <TrendingDown v-if="tx.type === 'expense'" class="h-4 w-4" />
                                <TrendingUp v-else-if="tx.type === 'income'" class="h-4 w-4" />
                                <ArrowRightLeft v-else class="h-4 w-4" />
                            </div>

                            <div>
                                <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ tx.title || tx.category?.name || 'Transaksi' }}
                                </h3>
                                <div class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                    <span>{{ tx.wallet?.name }}</span>
                                    <span>•</span>
                                    <span>{{ tx.user?.nickname || tx.user?.name?.split(' ')[0] }}</span>
                                    <span v-if="tx.scope === 'shared'" class="rounded bg-rose-500/10 px-1 py-0.2 text-[9px] font-medium text-rose-600 dark:text-rose-400">
                                        Kencan
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span
                                class="text-xs font-extrabold whitespace-nowrap block"
                                :class="[
                                    tx.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-zinc-100'
                                ]"
                            >{{ tx.type === 'expense' ? '-Rp ' : (tx.type === 'income' ? '+Rp ' : 'Rp ') }}{{ Number(tx.amount).toLocaleString('id-ID') }}</span>
                            <p class="text-[10px] text-zinc-400 whitespace-nowrap">
                                {{ new Date(tx.transaction_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="recentTransactions.length === 0"
                        class="p-6 text-center text-xs text-zinc-500"
                    >
                        Belum ada transaksi. Tap tombol (+) untuk mulai mencatat!
                    </div>
                </div>
            </div>
    </div>
</template>
