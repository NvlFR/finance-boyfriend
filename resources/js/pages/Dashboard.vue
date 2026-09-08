<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BanknoteArrowDown,
    BanknoteArrowUp,
    ChevronRight,
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
    Navigation,
    ShieldCheck,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import BirthdaySurprise from '@/components/BirthdaySurprise.vue';
import CashflowChart from '@/components/CashflowChart.vue';
import CategoryDonutChart from '@/components/CategoryDonutChart.vue';
import WalletCard from '@/components/WalletCard.vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import { dashboard as dashboardRoute } from '@/routes';
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

type ChartPeriod = '7d' | '30d' | 'month';

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
        chartPeriod?: ChartPeriod;
        chartPeriodLabel?: string;
        chartSpendingTotal?: number;
        chartSpendingByScope?: {
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
        chartPeriod: '7d',
        chartPeriodLabel: '7 Hari',
        chartSpendingTotal: 0,
        chartSpendingByScope: () => ({ shared: 0, personal: 0 }),
        upcomingSubscriptions: () => [],
        birthdaySurprise: null,
    },
);

const activeTab = ref<'all' | 'mine' | 'partner' | 'joint'>('all');
const isChartFiltering = ref(false);
const { openModal, openModalWithDefaults } = useTransactionModal();
const chartPeriods: Array<{ label: string; value: ChartPeriod }> = [
    { label: '7 Hari', value: '7d' },
    { label: '30 Hari', value: '30d' },
    { label: 'Bulan Ini', value: 'month' },
];

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

const userFirstName = computed(
    () => props.auth.user.nickname || props.auth.user.name.split(' ')[0],
);

const partnerFirstName = computed(() =>
    props.partner
        ? props.partner.nickname || props.partner.name.split(' ')[0]
        : 'Pasangan',
);

const userAvatarUrl = computed(
    () => props.auth.user.avatar_url || props.auth.user.avatar || '',
);

const partnerAvatarUrl = computed(
    () => props.partner?.avatar_url || props.partner?.avatar || '',
);

function formatCurrency(amount: number) {
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

function selectChartPeriod(period: ChartPeriod): void {
    if (period === props.chartPeriod || isChartFiltering.value) {
        return;
    }

    router.get(
        dashboardRoute.url(),
        { chart_period: period },
        {
            only: [
                'chartPeriod',
                'chartPeriodLabel',
                'dailyTrend',
                'categorySpending',
                'chartSpendingTotal',
                'chartSpendingByScope',
            ],
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => {
                isChartFiltering.value = true;
            },
            onFinish: () => {
                isChartFiltering.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Dashboard - Couple Finance" />

    <div class="mx-auto max-w-5xl space-y-4 sm:space-y-5">
        <header class="flex items-center justify-between gap-3 px-1">
            <div class="min-w-0">
                <h1
                    class="truncate text-xl font-black tracking-tight text-slate-950 sm:text-2xl dark:text-white"
                >
                    {{ greeting }}, {{ userFirstName }}
                </h1>
                <p
                    class="mt-0.5 flex items-center gap-1.5 truncate text-xs font-medium text-slate-500 dark:text-slate-400"
                >
                    <Heart
                        class="h-3.5 w-3.5 shrink-0 fill-current text-rose-500"
                    />
                    <span v-if="partner"
                        >{{ userFirstName }} & {{ partnerFirstName }}</span
                    >
                    <span v-else>{{ todayDateFormatted }}</span>
                </p>
            </div>

            <Link
                :href="coupleSpace ? '/couple-space' : '/settings/profile'"
                class="group flex shrink-0 items-center gap-2"
                :aria-label="
                    coupleSpace
                        ? 'Buka ruang pasangan'
                        : 'Buka pengaturan profil'
                "
            >
                <div class="flex -space-x-3">
                    <div
                        class="relative flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-indigo-600 text-sm font-black text-white shadow-sm ring-2 ring-indigo-100 dark:border-zinc-950 dark:ring-indigo-950"
                    >
                        <img
                            v-if="userAvatarUrl"
                            :src="userAvatarUrl"
                            :alt="auth.user.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ userFirstName.charAt(0) }}</span>
                    </div>
                    <div
                        v-if="partner"
                        class="relative flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-rose-500 text-sm font-black text-white shadow-sm ring-2 ring-rose-100 dark:border-zinc-950 dark:ring-rose-950"
                    >
                        <img
                            v-if="partnerAvatarUrl"
                            :src="partnerAvatarUrl"
                            :alt="partner.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ partnerFirstName.charAt(0) }}</span>
                    </div>
                </div>
                <ChevronRight
                    class="h-4 w-4 text-slate-400 transition-transform group-hover:translate-x-0.5"
                />
            </Link>
        </header>

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
            class="relative isolate overflow-hidden rounded-[1.75rem] border border-indigo-900/10 bg-gradient-to-br from-indigo-950 via-indigo-900 to-rose-900 p-5 text-white shadow-xl shadow-indigo-950/15 sm:p-7 dark:border-white/10"
        >
            <div
                class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_76%_18%,rgba(251,113,133,0.75),transparent_27%),radial-gradient(circle_at_53%_56%,rgba(251,146,60,0.5),transparent_34%),linear-gradient(135deg,transparent_35%,rgba(255,255,255,0.08))]"
            />
            <svg
                class="absolute right-0 bottom-0 left-0 -z-10 h-1/2 w-full opacity-70"
                viewBox="0 0 900 220"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <path
                    d="M0 170L120 85L220 145L360 40L500 155L620 70L760 135L900 55V220H0Z"
                    fill="rgba(15,23,42,.52)"
                />
                <path
                    d="M0 195L170 125L300 178L470 100L650 180L780 122L900 165V220H0Z"
                    fill="rgba(15,23,42,.72)"
                />
            </svg>

            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <span
                        class="inline-flex items-center gap-2 text-xs font-semibold text-white/80"
                    >
                        Total Kekayaan
                        <ShieldCheck class="h-4 w-4" />
                    </span>
                    <button
                        type="button"
                        @click="openModal"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-md transition-colors hover:bg-white/25"
                    >
                        <Plus class="h-4 w-4" /> Catat Transaksi
                    </button>
                </div>

                <div
                    class="text-[clamp(1.75rem,8vw,2.75rem)] font-black tracking-tight break-words"
                >
                    {{ formattedTotalNetWorth }}
                </div>

                <div
                    class="grid grid-cols-3 divide-x divide-white/20 border-t border-white/20 pt-4"
                >
                    <div class="min-w-0 pr-2">
                        <span class="text-[10px] font-medium text-white/70"
                            >Milik Kamu</span
                        >
                        <p class="mt-1 truncate text-xs font-black sm:text-sm">
                            {{ formatCurrency(userNetWorth) }}
                        </p>
                    </div>
                    <div class="min-w-0 px-2 sm:px-4">
                        <span class="text-[10px] font-medium text-white/70"
                            >Milik {{ partnerFirstName }}</span
                        >
                        <p class="mt-1 truncate text-xs font-black sm:text-sm">
                            {{ formatCurrency(partnerNetWorth) }}
                        </p>
                    </div>
                    <div class="min-w-0 pl-2 sm:pl-4">
                        <span class="text-[10px] font-medium text-white/70"
                            >Milik Bersama</span
                        >
                        <p class="mt-1 truncate text-xs font-black sm:text-sm">
                            {{ formatCurrency(jointNetWorth) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Quick Actions -->
        <div
            class="grid grid-cols-4 rounded-[1.5rem] border border-slate-200/80 bg-white px-2 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            aria-label="Aksi transaksi cepat"
        >
            <button
                type="button"
                class="group flex min-h-16 flex-col items-center justify-center gap-1.5 rounded-2xl text-[10px] font-bold text-slate-700 transition hover:bg-indigo-50 dark:text-zinc-200 dark:hover:bg-indigo-950/40"
                @click="openModalWithDefaults({ type: 'transfer' })"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 transition-transform group-hover:scale-105 dark:bg-indigo-950 dark:text-indigo-300"
                >
                    <ArrowRightLeft class="h-5 w-5" />
                </span>
                Transfer
            </button>
            <button
                type="button"
                class="group flex min-h-16 flex-col items-center justify-center gap-1.5 rounded-2xl text-[10px] font-bold text-slate-700 transition hover:bg-emerald-50 dark:text-zinc-200 dark:hover:bg-emerald-950/40"
                @click="openModalWithDefaults({ type: 'income' })"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 transition-transform group-hover:scale-105 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    <BanknoteArrowDown class="h-5 w-5" />
                </span>
                Pemasukan
            </button>
            <button
                type="button"
                class="group flex min-h-16 flex-col items-center justify-center gap-1.5 rounded-2xl text-[10px] font-bold text-slate-700 transition hover:bg-rose-50 dark:text-zinc-200 dark:hover:bg-rose-950/40"
                @click="openModalWithDefaults({ type: 'expense' })"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600 transition-transform group-hover:scale-105 dark:bg-rose-950 dark:text-rose-300"
                >
                    <BanknoteArrowUp class="h-5 w-5" />
                </span>
                Pengeluaran
            </button>
            <Link
                href="/goals"
                class="group flex min-h-16 flex-col items-center justify-center gap-1.5 rounded-2xl text-center text-[10px] font-bold text-slate-700 transition hover:bg-violet-50 dark:text-zinc-200 dark:hover:bg-violet-950/40"
            >
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-50 text-violet-700 transition-transform group-hover:scale-105 dark:bg-violet-950 dark:text-violet-300"
                >
                    <Target class="h-5 w-5" />
                </span>
                Buat Tabungan
            </Link>
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
        <div class="grid grid-cols-4 gap-2 text-center sm:grid-cols-8">
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

            <!-- Investments -->
            <Link
                href="/investments"
                class="flex flex-col items-center gap-1.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-sm transition-all hover:border-sky-300 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400"
                >
                    <TrendingUp class="h-5 w-5" />
                </div>
                <span
                    class="text-[10px] font-bold text-zinc-800 dark:text-zinc-200"
                    >Investasi</span
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
                class="flex flex-col items-center justify-center gap-1.5 rounded-2xl border border-sky-200/80 bg-gradient-to-r from-sky-50 to-indigo-50 p-3 text-center shadow-sm transition-all hover:border-sky-300 dark:border-sky-900/70 dark:from-sky-950/50 dark:to-indigo-950/40"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400"
                >
                    <Navigation class="h-5 w-5" />
                </div>
                <div>
                    <span
                        class="block text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >Perjalanan</span
                    >
                    <span class="sr-only"
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

        <!-- 📊 Interactive Financial Charts -->
        <div class="space-y-3">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Analisis Keuangan
                    </h2>
                    <p class="mt-0.5 text-[11px] text-zinc-500">
                        Pilih periode untuk kedua grafik
                    </p>
                </div>

                <div
                    role="group"
                    aria-label="Pilih periode grafik"
                    class="grid grid-cols-3 gap-1 rounded-2xl bg-zinc-100 p-1 dark:bg-zinc-800"
                >
                    <button
                        v-for="period in chartPeriods"
                        :key="period.value"
                        type="button"
                        :disabled="isChartFiltering"
                        :aria-pressed="chartPeriod === period.value"
                        class="min-h-11 rounded-xl px-3 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 disabled:cursor-wait"
                        :class="
                            chartPeriod === period.value
                                ? 'bg-white text-indigo-600 shadow-sm dark:bg-zinc-700 dark:text-indigo-300'
                                : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100'
                        "
                        @click="selectChartPeriod(period.value)"
                    >
                        {{ period.label }}
                    </button>
                </div>
            </div>

            <p v-if="isChartFiltering" class="sr-only" aria-live="polite">
                Memuat data grafik...
            </p>

            <div
                class="grid grid-cols-1 gap-4 transition-opacity md:grid-cols-2"
                :class="isChartFiltering ? 'opacity-60' : 'opacity-100'"
                :aria-busy="isChartFiltering"
            >
                <CashflowChart
                    :data="dailyTrend || []"
                    :period-label="chartPeriodLabel"
                />
                <CategoryDonutChart
                    :categories="categorySpending || []"
                    :total-spending="chartSpendingTotal"
                    :period-label="chartPeriodLabel"
                    :spending-by-scope="chartSpendingByScope"
                />
            </div>
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
