<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
    Target,
    Gift,
    Repeat,
    PieChart,
    Tag,
    Navigation,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import BirthdaySurprise from '@/components/BirthdaySurprise.vue';
import CashflowChart from '@/components/CashflowChart.vue';
import CategoryDonutChart from '@/components/CategoryDonutChart.vue';
import WalletCard from '@/components/WalletCard.vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import { index as settlementsIndex } from '@/routes/settlements';
import { index as tripsIndex } from '@/routes/trips';
import type { User } from '@/types/auth';
import type {
    BirthdaySurprisePayload,
    CoupleSpace,
    Wallet,
    Category,
    Transaction,
} from '@/types/finance';

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
        monthlyTransferFees?: number;
        dailySpending?: number;
        dailySpendingByUser?: {
            user: number;
            partner: number;
        };
        monthlySpendingByUser?: {
            user: number;
            partner: number;
        };
        monthlyIncomeByUser?: {
            user: number;
            partner: number;
        };
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
        birthdaySurprise?: BirthdaySurprisePayload | null;
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
        monthlyTransferFees: 0,
        dailySpending: 0,
        dailySpendingByUser: () => ({ user: 0, partner: 0 }),
        monthlySpendingByUser: () => ({ user: 0, partner: 0 }),
        monthlyIncomeByUser: () => ({ user: 0, partner: 0 }),
        categories: () => [],
        dailyTrend: () => [],
        categorySpending: () => [],
        upcomingSubscriptions: () => [],
        birthdaySurprise: null,
    },
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

    if (hour >= 4 && hour < 11) {
        return 'Selamat Pagi';
    }

    if (hour >= 11 && hour < 15) {
        return 'Selamat Siang';
    }

    if (hour >= 15 && hour < 18) {
        return 'Selamat Sore';
    }

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
</script>

<template>
    <Head title="Dashboard - Couple Finance" />

    <div class="space-y-6">
        <!-- 💖 Welcome Greeting Headline -->
        <div
            class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div>
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h1
                        class="min-w-0 text-xl font-black tracking-tight break-words text-zinc-900 sm:text-2xl dark:text-zinc-100"
                    >
                        {{ greeting }},
                        {{
                            auth.user.nickname || auth.user.name.split(' ')[0]
                        }}! 👋
                    </h1>
                    <span
                        v-if="partner"
                        class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                    >
                        <Heart class="h-3 w-3 animate-pulse fill-current" /> &
                        {{ partner.nickname || partner.name.split(' ')[0] }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ todayDateFormatted }} •
                    {{
                        coupleSpace
                            ? coupleSpace.name
                            : 'Kelola keuangan pribadi dan bersama'
                    }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    v-if="coupleSpace"
                    href="/couple-space"
                    class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3.5 py-1.5 text-xs font-bold text-zinc-700 shadow-xs transition-all hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                >
                    <Heart class="h-3.5 w-3.5 fill-current text-rose-500" />
                    <span>{{
                        partner
                            ? `${partner.nickname || partner.name.split(' ')[0]}`
                            : 'Undang Pasangan'
                    }}</span>
                </Link>
            </div>
        </div>

        <BirthdaySurprise
            v-if="birthdaySurprise"
            :surprise="birthdaySurprise"
        />

        <!-- First-Time Onboarding Prompt (If no Couple Space) -->
        <div
            v-if="!hasCoupleSpace"
            class="rounded-3xl border border-rose-200 bg-gradient-to-r from-rose-50 to-indigo-50 p-6 dark:border-rose-900/50 dark:bg-zinc-900"
        >
            <div
                class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div class="space-y-1">
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        <Sparkles class="h-5 w-5 text-rose-500" />
                        Selamat Datang di Couple Finance!
                    </h2>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                        Kelola uang pribadi, catat kebutuhan bersama, dan
                        wujudkan tabungan impian berdua secara transparan.
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
            <div
                class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-rose-500/20 blur-3xl"
            />
            <div
                class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-indigo-500/20 blur-3xl"
            />

            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-medium tracking-wider text-zinc-400 uppercase"
                    >
                        Total Kekayaan Berdua (Net Worth)
                    </span>
                    <button
                        type="button"
                        @click="openModal"
                        class="hidden items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-md transition-colors hover:bg-white/20 md:inline-flex"
                    >
                        <Plus class="h-4 w-4" /> Catat Transaksi
                    </button>
                </div>

                <div
                    class="text-2xl font-extrabold tracking-tight break-words min-[360px]:text-3xl sm:text-4xl"
                >
                    {{ formattedTotalNetWorth }}
                </div>

                <div
                    class="grid grid-cols-3 gap-2 border-t border-white/10 pt-2"
                >
                    <div>
                        <span class="text-[11px] text-indigo-300"
                            >Punya Kamu</span
                        >
                        <p class="truncate text-xs font-bold sm:text-sm">
                            {{ formatCurrency(userNetWorth) }}
                        </p>
                    </div>
                    <div>
                        <span class="text-[11px] text-rose-300">{{
                            partner
                                ? partner.nickname || partner.name.split(' ')[0]
                                : 'Pasangan'
                        }}</span>
                        <p class="truncate text-xs font-bold sm:text-sm">
                            {{ formatCurrency(partnerNetWorth) }}
                        </p>
                    </div>
                    <div>
                        <span class="text-[11px] text-emerald-300"
                            >Kas Bersama</span
                        >
                        <p class="truncate text-xs font-bold sm:text-sm">
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
            <div
                class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-sm"
                    >
                        <Repeat class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="text-xs font-bold text-amber-900 dark:text-amber-300"
                        >
                            Pengingat Tagihan Jatuh Tempo ({{
                                upcomingSubscriptions.length
                            }})
                        </h3>
                        <p
                            class="mt-0.5 text-[11px] text-zinc-600 dark:text-zinc-400"
                        >
                            <strong
                                class="break-words text-zinc-900 dark:text-zinc-100"
                                >{{ upcomingSubscriptions[0].name }}</strong
                            >
                            (Rp
                            {{
                                Number(
                                    upcomingSubscriptions[0].amount,
                                ).toLocaleString('id-ID')
                            }}) jatuh tempo
                            {{
                                new Date(
                                    upcomingSubscriptions[0].next_billing_date,
                                ).toLocaleDateString('id-ID', {
                                    day: 'numeric',
                                    month: 'short',
                                })
                            }}.
                        </p>
                    </div>
                </div>

                <Link
                    href="/subscriptions"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center self-start rounded-full bg-amber-500/20 px-3 py-1.5 text-[11px] font-bold text-amber-700 transition-colors hover:bg-amber-500/30 sm:self-auto dark:text-amber-300"
                >
                    Lihat Tagihan &rarr;
                </Link>
            </div>
        </div>

        <!-- Quick Couple Features Grid -->
        <div class="grid grid-cols-3 gap-2 text-center sm:grid-cols-6">
            <!-- Goals -->
            <Link
                href="/goals"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-indigo-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                >
                    <Target class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Tabungan</span
                >
            </Link>

            <!-- Wishlist -->
            <Link
                href="/wishlists"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                >
                    <Gift class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Wishlist</span
                >
            </Link>

            <!-- Subscriptions -->
            <Link
                href="/subscriptions"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-amber-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                >
                    <Repeat class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Langganan</span
                >
            </Link>

            <!-- Budgets -->
            <Link
                href="/budgets"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-emerald-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                >
                    <PieChart class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Anggaran</span
                >
            </Link>

            <!-- Categories -->
            <Link
                href="/categories"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-purple-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400"
                >
                    <Tag class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Kategori</span
                >
            </Link>

            <!-- Couple Space -->
            <Link
                href="/couple-space"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-pink-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-pink-500/10 text-pink-600 dark:bg-pink-500/20 dark:text-pink-400"
                >
                    <Heart class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Pasangan</span
                >
            </Link>

            <Link
                :href="settlementsIndex()"
                prefetch
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-amber-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                >
                    <Handshake class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Talangan</span
                >
            </Link>

            <!-- Live Trip Tracker -->
            <Link
                :href="tripsIndex()"
                prefetch
                class="col-span-2 flex min-h-16 items-center justify-center gap-3 rounded-2xl border border-sky-200/80 bg-gradient-to-r from-sky-50 to-indigo-50 px-4 py-3 text-left shadow-sm transition-all hover:border-sky-300 sm:col-span-5 dark:border-sky-900/70 dark:from-sky-950/50 dark:to-indigo-950/40"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400"
                >
                    <Navigation class="h-5 w-5" />
                </div>
                <div>
                    <span
                        class="block text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >Perjalanan</span
                    >
                    <span
                        class="block text-[10px] text-zinc-500 dark:text-zinc-400"
                        >Bagikan lokasi dan pantau perjalanan pasangan</span
                    >
                </div>
            </Link>
        </div>

        <!-- Settlement Alert Banner (If Debt Exists) -->
        <div
            v-if="settlementDebt && settlementDebt.amount_owed > 0"
            class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 shadow-sm dark:border-amber-500/20"
        >
            <div
                class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white"
                    >
                        <Handshake class="h-5 w-5" />
                    </div>
                    <div>
                        <h3
                            class="text-xs font-bold text-amber-900 dark:text-amber-300"
                        >
                            {{
                                settlementDebt.debtor_id === auth.user.id
                                    ? 'Kamu Perlu Melunasi Talangan Pasangan'
                                    : `${settlementDebt.debtor_name} Perlu Melunasi Talanganmu`
                            }}
                        </h3>
                        <p
                            class="text-xs text-amber-800/80 dark:text-amber-400"
                        >
                            Sebesar
                            <span class="font-bold"
                                >Rp
                                {{
                                    Number(
                                        settlementDebt.amount_owed,
                                    ).toLocaleString()
                                }}</span
                            >
                            ({{ settlementDebt.unsettled_splits_count }}
                            transaksi bersama)
                        </p>
                    </div>
                </div>

                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <Link
                        :href="settlementsIndex()"
                        class="w-full rounded-xl bg-amber-500 px-3.5 py-2 text-center text-xs font-semibold text-white shadow-sm transition-colors hover:bg-amber-600 sm:w-auto"
                    >
                        Tinjau Rincian Talangan
                    </Link>
                </div>
            </div>
        </div>

        <!-- Monthly In/Out Cashflow Summary -->
        <div class="grid grid-cols-2 gap-3">
            <div
                class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400"
                >
                    <ArrowDownLeft class="h-4 w-4" />
                    <span class="text-xs font-medium">Pemasukan Bulan Ini</span>
                </div>
                <p
                    class="mt-2 text-lg font-bold text-zinc-900 dark:text-zinc-100"
                >
                    {{ formattedMonthlyIncome }}
                </p>
            </div>
            <div
                class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center gap-2 text-rose-600 dark:text-rose-400"
                >
                    <ArrowUpRight class="h-4 w-4" />
                    <span class="text-xs font-medium"
                        >Pengeluaran Bulan Ini</span
                    >
                </div>
                <p
                    class="mt-2 text-lg font-bold text-zinc-900 dark:text-zinc-100"
                >
                    {{ formattedMonthlySpending }}
                </p>
                <p
                    v-if="monthlyTransferFees > 0"
                    class="mt-1 text-[10px] text-amber-600 dark:text-amber-400"
                >
                    Termasuk admin transfer
                    {{ formatCurrency(monthlyTransferFees) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div
                class="col-span-2 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-zinc-500">
                            Pengeluaran Hari Ini
                        </p>
                        <p
                            class="mt-1 text-xl font-black text-rose-600 dark:text-rose-400"
                        >
                            {{ formatCurrency(dailySpending) }}
                        </p>
                    </div>
                    <div class="text-right text-[11px] leading-5 text-zinc-500">
                        <p>
                            Kamu:
                            <strong class="text-zinc-800 dark:text-zinc-200">{{
                                formatCurrency(dailySpendingByUser.user)
                            }}</strong>
                        </p>
                        <p v-if="partner">
                            {{
                                partner.nickname || partner.name.split(' ')[0]
                            }}:
                            <strong class="text-zinc-800 dark:text-zinc-200">{{
                                formatCurrency(dailySpendingByUser.partner)
                            }}</strong>
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-indigo-200/70 bg-indigo-50/60 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/30"
            >
                <p
                    class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400"
                >
                    Kamu · Bulan Ini
                </p>
                <p
                    class="mt-1 text-sm font-black break-words text-zinc-900 dark:text-zinc-100"
                >
                    <span class="block text-emerald-600 dark:text-emerald-400">
                        Masuk {{ formatCurrency(monthlyIncomeByUser.user) }}
                    </span>
                    <span class="mt-1 block text-rose-600 dark:text-rose-400">
                        Keluar {{ formatCurrency(monthlySpendingByUser.user) }}
                    </span>
                </p>
            </div>
            <div
                class="rounded-2xl border border-rose-200/70 bg-rose-50/60 p-4 dark:border-rose-900/60 dark:bg-rose-950/30"
            >
                <p
                    class="text-[11px] font-semibold text-rose-600 dark:text-rose-400"
                >
                    {{
                        partner
                            ? partner.nickname || partner.name.split(' ')[0]
                            : 'Pasangan'
                    }}
                    · Bulan Ini
                </p>
                <p
                    class="mt-1 text-sm font-black break-words text-zinc-900 dark:text-zinc-100"
                >
                    <span class="block text-emerald-600 dark:text-emerald-400">
                        Masuk {{ formatCurrency(monthlyIncomeByUser.partner) }}
                    </span>
                    <span class="mt-1 block text-rose-600 dark:text-rose-400">
                        Keluar
                        {{ formatCurrency(monthlySpendingByUser.partner) }}
                    </span>
                </p>
            </div>
        </div>

        <!-- 📊 Interactive Financial Charts (7-Day Cashflow Trend & Monthly Category Donut) -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                    <h2
                        class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Dompet
                    </h2>
                </div>
                <Link
                    href="/wallets"
                    class="inline-flex min-h-11 items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 shadow-sm transition-colors hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60"
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
                    class="min-h-11 shrink-0 rounded-full px-3 py-1 font-medium transition-all"
                    :class="
                        activeTab === 'all'
                            ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                            : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                    "
                >
                    Semua ({{ wallets.length }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'mine'"
                    class="min-h-11 shrink-0 rounded-full px-3 py-1 font-medium transition-all"
                    :class="
                        activeTab === 'mine'
                            ? 'bg-indigo-600 text-white'
                            : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                    "
                >
                    Punya Kamu ({{ userWallets.length }})
                </button>
                <button
                    v-if="partner"
                    type="button"
                    @click="activeTab = 'partner'"
                    class="min-h-11 shrink-0 rounded-full px-3 py-1 font-medium transition-all"
                    :class="
                        activeTab === 'partner'
                            ? 'bg-rose-600 text-white'
                            : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                    "
                >
                    {{ partner.nickname || partner.name.split(' ')[0] }} ({{
                        partnerWallets.length
                    }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'joint'"
                    class="min-h-11 shrink-0 rounded-full px-3 py-1 font-medium transition-all"
                    :class="
                        activeTab === 'joint'
                            ? 'bg-emerald-600 text-white'
                            : 'bg-zinc-200/60 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                    "
                >
                    Kas Bersama ({{ jointWallets.length }})
                </button>
            </div>

            <!-- Wallet Grid -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                <WalletCard
                    v-for="w in displayedWallets"
                    :key="w.id"
                    :wallet="w"
                    :is-joint="w.type === 'joint'"
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
                <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                    Aktivitas Terkini
                </h2>
                <Link
                    href="/transactions"
                    class="inline-flex min-h-11 items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 shadow-sm transition-colors hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60"
                >
                    Lihat Semua
                    <ArrowUpRight class="h-3.5 w-3.5" />
                </Link>
            </div>

            <div
                class="divide-y divide-zinc-100 rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:divide-zinc-800/80 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    v-for="tx in recentTransactions"
                    :key="tx.id"
                    class="flex items-center justify-between p-3.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white"
                            :style="{
                                backgroundColor:
                                    tx.category?.color || '#6366F1',
                            }"
                        >
                            <TrendingDown
                                v-if="tx.type === 'expense'"
                                class="h-4 w-4"
                            />
                            <TrendingUp
                                v-else-if="tx.type === 'income'"
                                class="h-4 w-4"
                            />
                            <ArrowRightLeft v-else class="h-4 w-4" />
                        </div>

                        <div class="min-w-0">
                            <h3
                                class="truncate text-xs font-semibold text-zinc-900 dark:text-zinc-100"
                            >
                                {{
                                    tx.title || tx.category?.name || 'Transaksi'
                                }}
                            </h3>
                            <div
                                class="flex min-w-0 items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400"
                            >
                                <span class="truncate">{{
                                    tx.wallet?.name
                                }}</span>
                                <span>•</span>
                                <span>{{
                                    tx.user?.nickname ||
                                    tx.user?.name?.split(' ')[0]
                                }}</span>
                                <span
                                    v-if="tx.scope === 'shared'"
                                    class="py-0.2 rounded bg-rose-500/10 px-1 text-[9px] font-medium text-rose-600 dark:text-rose-400"
                                >
                                    Bersama
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 text-right">
                        <span
                            class="block text-xs font-extrabold whitespace-nowrap"
                            :class="[
                                tx.type === 'income'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-zinc-900 dark:text-zinc-100',
                            ]"
                            >{{
                                tx.type === 'expense'
                                    ? '-Rp '
                                    : tx.type === 'income'
                                      ? '+Rp '
                                      : 'Rp '
                            }}{{
                                Number(tx.amount).toLocaleString('id-ID')
                            }}</span
                        >
                        <p class="text-[10px] whitespace-nowrap text-zinc-400">
                            {{
                                new Date(
                                    tx.transaction_date,
                                ).toLocaleDateString('id-ID', {
                                    day: 'numeric',
                                    month: 'short',
                                })
                            }}
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
