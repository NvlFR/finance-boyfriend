<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BarChart3,
    Coins,
    Pencil,
    Plus,
    Trash2,
    TrendingUp,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import FormErrorSummary from '@/components/FormErrorSummary.vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import {
    destroy as investmentDestroy,
    store as investmentStore,
    transact as investmentTransact,
} from '@/routes/investments';
import investmentPrice from '@/routes/investments/price';
import type { User } from '@/types/auth';
import type { Investment, Wallet } from '@/types/finance';

const props = defineProps<{
    investments: Investment[];
    wallets: Wallet[];
    summary: {
        market_value: number | string;
        cost_basis: number | string;
        unrealized_profit_loss: number | string;
        realized_profit_loss: number | string;
    };
    auth: { user: User };
}>();

const isCreateOpen = ref(false);
const isTradeOpen = ref(false);
const isPriceOpen = ref(false);
const selectedInvestment = ref<Investment | null>(null);
const investmentToDelete = ref<Investment | null>(null);
const isDeleting = ref(false);
const isAnyModalOpen = computed(
    () => isCreateOpen.value || isTradeOpen.value || isPriceOpen.value,
);

function closeModal(): void {
    isCreateOpen.value = false;
    isTradeOpen.value = false;
    isPriceOpen.value = false;
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    isAnyModalOpen,
    closeModal,
);

const createForm = useForm({
    name: '',
    symbol: '',
    asset_type: 'stock',
    scope: 'personal',
});

const tradeForm = useForm({
    type: 'buy' as 'buy' | 'sell',
    input_mode: 'amount' as 'amount' | 'quantity',
    wallet_id: props.wallets[0]?.id ?? null,
    amount: '' as number | string,
    quantity: '' as number | string,
    unit_price: '' as number | string,
    fee_amount: 0 as number | string,
    transaction_date: localDate(),
    client_reference: '',
    notes: '',
});

const priceForm = useForm({ current_price: '' as number | string });

const returnPercentage = computed(() => {
    const cost = Number(props.summary.cost_basis);

    return cost > 0
        ? (Number(props.summary.unrealized_profit_loss) / cost) * 100
        : 0;
});

const estimatedGross = computed(() =>
    tradeForm.type === 'buy' && tradeForm.input_mode === 'amount'
        ? Number(tradeForm.amount || 0)
        : Number(tradeForm.quantity || 0) * Number(tradeForm.unit_price || 0),
);

const estimatedQuantity = computed(() => {
    if (tradeForm.input_mode === 'quantity') {
        return Number(tradeForm.quantity || 0);
    }

    const unitPrice = Number(tradeForm.unit_price || 0);

    return unitPrice > 0 ? Number(tradeForm.amount || 0) / unitPrice : 0;
});

const estimatedWalletMutation = computed(() => {
    const fee = Number(tradeForm.fee_amount || 0);

    return tradeForm.type === 'buy'
        ? estimatedGross.value + fee
        : Math.max(0, estimatedGross.value - fee);
});

function localDate(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

function createClientReference(): string {
    return typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function money(value: number | string): string {
    return `Rp ${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 })}`;
}

function quantity(value: number | string): string {
    return Number(value).toLocaleString('id-ID', { maximumFractionDigits: 8 });
}

function marketValue(investment: Investment): number {
    return Number(investment.quantity) * Number(investment.current_price);
}

function unrealizedProfit(investment: Investment): number {
    return (
        marketValue(investment) -
        Number(investment.quantity) * Number(investment.average_buy_price)
    );
}

function canManage(investment: Investment): boolean {
    return (
        investment.scope === 'shared' ||
        investment.user_id === props.auth.user.id
    );
}

function assetLabel(type: Investment['asset_type']): string {
    return {
        stock: 'Saham',
        mutual_fund: 'Reksa Dana',
        crypto: 'Kripto',
        gold: 'Emas',
        deposit: 'Deposito',
        other: 'Lainnya',
    }[type];
}

function openCreate(): void {
    createForm.clearErrors();
    isCreateOpen.value = true;
}

function submitCreate(): void {
    createForm.post(investmentStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            isCreateOpen.value = false;
        },
    });
}

function openTrade(investment: Investment, type: 'buy' | 'sell'): void {
    selectedInvestment.value = investment;
    tradeForm.clearErrors();
    tradeForm.type = type;
    tradeForm.input_mode = type === 'buy' ? 'amount' : 'quantity';
    tradeForm.wallet_id = props.wallets[0]?.id ?? null;
    tradeForm.amount = '';
    tradeForm.quantity = '';
    tradeForm.unit_price = investment.current_price || '';
    tradeForm.fee_amount = 0;
    tradeForm.transaction_date = localDate();
    tradeForm.client_reference = createClientReference();
    tradeForm.notes = '';
    isTradeOpen.value = true;
}

function setTradeInputMode(mode: 'amount' | 'quantity'): void {
    tradeForm.input_mode = mode;
    tradeForm.amount = '';
    tradeForm.quantity = '';
    tradeForm.clearErrors('amount', 'quantity');
}

function submitTrade(): void {
    if (!selectedInvestment.value) {
        return;
    }

    tradeForm.post(investmentTransact.url(selectedInvestment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isTradeOpen.value = false;
            selectedInvestment.value = null;
        },
    });
}

function openPrice(investment: Investment): void {
    selectedInvestment.value = investment;
    priceForm.clearErrors();
    priceForm.current_price = investment.current_price;
    isPriceOpen.value = true;
}

function submitPrice(): void {
    if (!selectedInvestment.value) {
        return;
    }

    priceForm.patch(investmentPrice.update.url(selectedInvestment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isPriceOpen.value = false;
            selectedInvestment.value = null;
        },
    });
}

function confirmDelete(): void {
    if (!investmentToDelete.value) {
        return;
    }

    router.delete(investmentDestroy.url(investmentToDelete.value.id), {
        preserveScroll: true,
        onStart: () => (isDeleting.value = true),
        onSuccess: () => (investmentToDelete.value = null),
        onFinish: () => (isDeleting.value = false),
    });
}
</script>

<template>
    <Head title="Investasi - Couple Finance" />

    <div class="space-y-5 pb-4">
        <header class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    href="/dashboard"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200"
                    aria-label="Kembali ke dashboard"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div class="min-w-0">
                    <h1
                        class="text-lg font-black text-zinc-900 dark:text-white"
                    >
                        Investasi
                    </h1>
                    <p class="text-xs text-zinc-500">
                        Pantau aset tanpa mencampurnya dengan pengeluaran
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full bg-sky-600 px-3.5 text-xs font-bold text-white shadow-lg shadow-sky-500/20"
                @click="openCreate"
            >
                <Plus class="h-4 w-4" /> Aset
            </button>
        </header>

        <section
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-700 via-indigo-900 to-zinc-950 p-5 text-white shadow-xl"
        >
            <div
                class="absolute -top-10 -right-10 h-36 w-36 rounded-full bg-cyan-400/20 blur-3xl"
            />
            <div class="relative space-y-4">
                <div class="flex items-center justify-between">
                    <span
                        class="text-[11px] font-bold tracking-wider text-sky-200 uppercase"
                    >
                        Nilai Portofolio
                    </span>
                    <BarChart3 class="h-5 w-5 text-sky-300" />
                </div>
                <p class="text-3xl font-black tracking-tight break-words">
                    {{ money(summary.market_value) }}
                </p>
                <div
                    class="grid grid-cols-2 gap-3 border-t border-white/15 pt-3"
                >
                    <div>
                        <p class="text-[10px] text-sky-200">Modal tersisa</p>
                        <p class="text-sm font-bold">
                            {{ money(summary.cost_basis) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] text-sky-200">
                            Untung/rugi berjalan
                        </p>
                        <p
                            class="text-sm font-bold"
                            :class="
                                Number(summary.unrealized_profit_loss) >= 0
                                    ? 'text-emerald-300'
                                    : 'text-rose-300'
                            "
                        >
                            {{ money(summary.unrealized_profit_loss) }}
                            <span class="text-[10px]"
                                >({{ returnPercentage.toFixed(2) }}%)</span
                            >
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="investments.length === 0"
            class="rounded-3xl border border-dashed border-zinc-300 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600"
            >
                <TrendingUp class="h-7 w-7" />
            </div>
            <h2 class="mt-3 text-sm font-black text-zinc-900 dark:text-white">
                Belum ada aset investasi
            </h2>
            <p class="mt-1 text-xs leading-relaxed text-zinc-500">
                Tambahkan nama aset dulu, lalu catat pembelian dari dompet yang
                dipakai.
            </p>
            <button
                type="button"
                class="mt-4 min-h-11 rounded-2xl bg-sky-600 px-5 text-xs font-bold text-white"
                @click="openCreate"
            >
                Tambah Aset Pertama
            </button>
        </div>

        <section v-else class="space-y-3">
            <article
                v-for="investment in investments"
                :key="investment.id"
                class="rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400"
                        >
                            <Coins class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <h2
                                    class="truncate text-sm font-black text-zinc-900 dark:text-white"
                                >
                                    {{ investment.name }}
                                </h2>
                                <span
                                    v-if="investment.symbol"
                                    class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[9px] font-bold text-zinc-500 dark:bg-zinc-800"
                                >
                                    {{ investment.symbol }}
                                </span>
                            </div>
                            <p class="text-[10px] text-zinc-500">
                                {{ assetLabel(investment.asset_type) }} ·
                                {{
                                    investment.scope === 'shared'
                                        ? 'Bersama'
                                        : investment.user?.nickname ||
                                          investment.user?.name ||
                                          'Pribadi'
                                }}
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="canManage(investment)"
                        type="button"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Perbarui harga"
                        @click="openPrice(investment)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                </div>

                <div
                    class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-950/60"
                >
                    <div>
                        <p class="text-[10px] text-zinc-500">Nilai sekarang</p>
                        <p
                            class="text-sm font-black text-zinc-900 dark:text-white"
                        >
                            {{ money(marketValue(investment)) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-zinc-500">Untung/rugi</p>
                        <p
                            class="text-sm font-black"
                            :class="
                                unrealizedProfit(investment) >= 0
                                    ? 'text-emerald-600'
                                    : 'text-rose-600'
                            "
                        >
                            {{ money(unrealizedProfit(investment)) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-500">Unit dimiliki</p>
                        <p
                            class="text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                            {{ quantity(investment.quantity) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-zinc-500">Harga / unit</p>
                        <p
                            class="text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                            {{ money(investment.current_price) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="canManage(investment)"
                    class="mt-3 grid grid-cols-2 gap-2"
                >
                    <button
                        type="button"
                        class="min-h-11 rounded-2xl bg-emerald-600 text-xs font-bold text-white"
                        @click="openTrade(investment, 'buy')"
                    >
                        Beli
                    </button>
                    <button
                        type="button"
                        class="min-h-11 rounded-2xl border border-rose-200 text-xs font-bold text-rose-600 dark:border-rose-900"
                        @click="openTrade(investment, 'sell')"
                    >
                        Jual
                    </button>
                </div>

                <details
                    v-if="investment.transactions?.length"
                    class="group mt-3"
                >
                    <summary
                        class="flex min-h-11 cursor-pointer list-none items-center justify-between text-xs font-bold text-zinc-600 dark:text-zinc-300"
                    >
                        Riwayat aset ({{ investment.transactions.length }})
                        <span class="text-zinc-400 group-open:rotate-180"
                            >⌄</span
                        >
                    </summary>
                    <div
                        class="space-y-2 border-t border-zinc-100 pt-2 dark:border-zinc-800"
                    >
                        <div
                            v-for="movement in investment.transactions"
                            :key="movement.id"
                            class="flex items-center justify-between gap-3 py-1"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-bold text-zinc-800 dark:text-zinc-200"
                                >
                                    {{
                                        movement.type === 'buy'
                                            ? 'Beli'
                                            : 'Jual'
                                    }}
                                    {{ quantity(movement.quantity) }} unit
                                </p>
                                <p class="truncate text-[10px] text-zinc-500">
                                    {{
                                        new Date(
                                            movement.transaction_date,
                                        ).toLocaleDateString('id-ID')
                                    }}
                                    · {{ movement.wallet?.name }}
                                </p>
                            </div>
                            <p
                                class="shrink-0 text-xs font-black"
                                :class="
                                    movement.type === 'buy'
                                        ? 'text-rose-600'
                                        : 'text-emerald-600'
                                "
                            >
                                {{ movement.type === 'buy' ? '-' : '+'
                                }}{{
                                    money(
                                        Number(movement.gross_amount) +
                                            (movement.type === 'buy'
                                                ? Number(movement.fee_amount)
                                                : -Number(movement.fee_amount)),
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </details>

                <button
                    v-if="
                        canManage(investment) &&
                        Number(investment.quantity) === 0
                    "
                    type="button"
                    class="mt-2 inline-flex min-h-11 items-center gap-1 text-[11px] font-bold text-rose-600"
                    @click="investmentToDelete = investment"
                >
                    <Trash2 class="h-3.5 w-3.5" /> Hapus aset kosong
                </button>
            </article>
        </section>

        <Teleport to="body">
            <div
                v-if="isAnyModalOpen"
                class="fixed inset-0 z-[70] flex items-end bg-zinc-950/70 p-0 backdrop-blur-sm sm:items-center sm:justify-center sm:p-4"
                @click.self="closeModal"
            >
                <div
                    ref="dialogRef"
                    role="dialog"
                    aria-modal="true"
                    tabindex="-1"
                    class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl border border-zinc-200 bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl dark:border-zinc-800 dark:bg-zinc-900"
                    @keydown="handleDialogKeydown"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-3 dark:border-zinc-800"
                    >
                        <h2
                            class="text-base font-black text-zinc-900 dark:text-white"
                        >
                            {{
                                isCreateOpen
                                    ? 'Tambah Aset'
                                    : isPriceOpen
                                      ? 'Update Harga'
                                      : tradeForm.type === 'buy'
                                        ? 'Beli Investasi'
                                        : 'Jual Investasi'
                            }}
                        </h2>
                        <button
                            type="button"
                            class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            aria-label="Tutup"
                            @click="closeModal"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form
                        v-if="isCreateOpen"
                        class="mt-4 space-y-4"
                        @submit.prevent="submitCreate"
                    >
                        <div>
                            <label
                                class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                >Nama aset</label
                            >
                            <input
                                v-model="createForm.name"
                                required
                                maxlength="100"
                                placeholder="Contoh: Emas Digital"
                                class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Kode (opsional)</label
                                >
                                <input
                                    v-model="createForm.symbol"
                                    maxlength="30"
                                    placeholder="BBCA"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Jenis</label
                                >
                                <select
                                    v-model="createForm.asset_type"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                >
                                    <option value="stock">Saham</option>
                                    <option value="mutual_fund">
                                        Reksa Dana
                                    </option>
                                    <option value="crypto">Kripto</option>
                                    <option value="gold">Emas</option>
                                    <option value="deposit">Deposito</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                >Kepemilikan</label
                            >
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                <button
                                    v-for="option in [
                                        { value: 'personal', label: 'Pribadi' },
                                        { value: 'shared', label: 'Bersama' },
                                    ]"
                                    :key="option.value"
                                    type="button"
                                    class="min-h-11 rounded-xl border text-xs font-bold"
                                    :class="
                                        createForm.scope === option.value
                                            ? 'border-sky-600 bg-sky-50 text-sky-700 dark:bg-sky-950/40'
                                            : 'border-zinc-200 text-zinc-500 dark:border-zinc-700'
                                    "
                                    @click="createForm.scope = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                        <FormErrorSummary :errors="createForm.errors" />
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="min-h-11 w-full rounded-2xl bg-sky-600 text-xs font-bold text-white disabled:opacity-50"
                        >
                            {{
                                createForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Aset'
                            }}
                        </button>
                    </form>

                    <form
                        v-else-if="isTradeOpen && selectedInvestment"
                        class="mt-4 space-y-4"
                        @submit.prevent="submitTrade"
                    >
                        <div
                            class="rounded-2xl bg-sky-500/10 p-3 text-xs text-sky-700 dark:text-sky-300"
                        >
                            <strong>{{ selectedInvestment.name }}</strong> ·
                            tersedia
                            {{ quantity(selectedInvestment.quantity) }} unit
                        </div>
                        <div
                            v-if="wallets.length === 0"
                            class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                        >
                            Buat dompet pribadi atau bersama dulu sebelum
                            mencatat investasi.
                        </div>
                        <div>
                            <label
                                class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                >Dompet
                                {{
                                    tradeForm.type === 'buy'
                                        ? 'pembayaran'
                                        : 'penerima'
                                }}</label
                            >
                            <select
                                v-model="tradeForm.wallet_id"
                                required
                                class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            >
                                <option
                                    v-for="wallet in wallets"
                                    :key="wallet.id"
                                    :value="wallet.id"
                                >
                                    {{ wallet.name }} ·
                                    {{ money(wallet.balance) }}
                                </option>
                            </select>
                        </div>

                        <div v-if="tradeForm.type === 'buy'">
                            <label
                                class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                >Cara memasukkan pembelian</label
                            >
                            <div
                                class="mt-1 grid grid-cols-2 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800"
                            >
                                <button
                                    type="button"
                                    class="min-h-10 rounded-lg text-xs font-bold transition-colors"
                                    :class="
                                        tradeForm.input_mode === 'amount'
                                            ? 'bg-white text-sky-700 shadow-sm dark:bg-zinc-700 dark:text-sky-300'
                                            : 'text-zinc-500'
                                    "
                                    @click="setTradeInputMode('amount')"
                                >
                                    Nominal Rupiah
                                </button>
                                <button
                                    type="button"
                                    class="min-h-10 rounded-lg text-xs font-bold transition-colors"
                                    :class="
                                        tradeForm.input_mode === 'quantity'
                                            ? 'bg-white text-sky-700 shadow-sm dark:bg-zinc-700 dark:text-sky-300'
                                            : 'text-zinc-500'
                                    "
                                    @click="setTradeInputMode('quantity')"
                                >
                                    Jumlah Unit
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-if="
                                    tradeForm.type === 'buy' &&
                                    tradeForm.input_mode === 'amount'
                                "
                            >
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Nominal pembelian (Rp)</label
                                ><CurrencyInput
                                    v-model="tradeForm.amount"
                                    required
                                    allow-decimals
                                    min="0.01"
                                    placeholder="Contoh: 100000"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </div>
                            <div v-else>
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Jumlah unit</label
                                ><input
                                    v-model="tradeForm.quantity"
                                    required
                                    type="text"
                                    min="0.00000001"
                                    step="0.00000001"
                                    inputmode="decimal"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Harga / unit</label
                                ><CurrencyInput
                                    v-model="tradeForm.unit_price"
                                    required
                                    allow-decimals
                                    min="0.01"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Biaya admin</label
                                ><CurrencyInput
                                    v-model="tradeForm.fee_amount"
                                    allow-decimals
                                    min="0"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </div>
                            <div>
                                <label
                                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                    >Tanggal</label
                                ><input
                                    v-model="tradeForm.transaction_date"
                                    required
                                    type="date"
                                    class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                                />
                            </div>
                        </div>
                        <div
                            class="space-y-1 rounded-2xl bg-zinc-100 p-3 text-xs dark:bg-zinc-800"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-zinc-500">{{
                                    tradeForm.type === 'buy'
                                        ? 'Saldo berkurang'
                                        : 'Saldo bertambah'
                                }}</span>
                                <strong class="text-zinc-900 dark:text-white">{{
                                    money(estimatedWalletMutation)
                                }}</strong>
                            </div>
                            <div
                                v-if="
                                    tradeForm.type === 'buy' &&
                                    tradeForm.input_mode === 'amount'
                                "
                                class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-1 dark:border-zinc-700"
                            >
                                <span class="text-zinc-500"
                                    >Perkiraan unit diterima</span
                                >
                                <strong class="text-sky-700 dark:text-sky-300">
                                    {{ quantity(estimatedQuantity) }} unit
                                </strong>
                            </div>
                        </div>
                        <FormErrorSummary :errors="tradeForm.errors" />
                        <button
                            type="submit"
                            :disabled="
                                tradeForm.processing || wallets.length === 0
                            "
                            class="min-h-11 w-full rounded-2xl text-xs font-bold text-white disabled:opacity-50"
                            :class="
                                tradeForm.type === 'buy'
                                    ? 'bg-emerald-600'
                                    : 'bg-rose-600'
                            "
                        >
                            {{
                                tradeForm.processing
                                    ? 'Memproses...'
                                    : tradeForm.type === 'buy'
                                      ? 'Catat Pembelian'
                                      : 'Catat Penjualan'
                            }}
                        </button>
                    </form>

                    <form
                        v-else-if="isPriceOpen && selectedInvestment"
                        class="mt-4 space-y-4"
                        @submit.prevent="submitPrice"
                    >
                        <p class="text-xs leading-relaxed text-zinc-500">
                            Masukkan harga pasar terbaru per unit untuk
                            <strong class="text-zinc-900 dark:text-white">{{
                                selectedInvestment.name
                            }}</strong
                            >. Ini hanya memperbarui valuasi, tidak mengubah
                            saldo dompet.
                        </p>
                        <div>
                            <label
                                class="text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                                >Harga terkini / unit</label
                            ><CurrencyInput
                                v-model="priceForm.current_price"
                                required
                                allow-decimals
                                min="0"
                                class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            />
                        </div>
                        <FormErrorSummary :errors="priceForm.errors" />
                        <button
                            type="submit"
                            :disabled="priceForm.processing"
                            class="min-h-11 w-full rounded-2xl bg-sky-600 text-xs font-bold text-white disabled:opacity-50"
                        >
                            {{
                                priceForm.processing
                                    ? 'Menyimpan...'
                                    : 'Update Harga'
                            }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <ConfirmActionDialog
            :open="investmentToDelete !== null"
            title="Hapus aset investasi?"
            description="Riwayat aset ini juga akan dihapus. Aset hanya bisa dihapus saat jumlah unit sudah nol."
            :processing="isDeleting"
            @update:open="(open) => !open && (investmentToDelete = null)"
            @confirm="confirmDelete"
        />
    </div>
</template>
