<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Plus,
    ArrowRightLeft,
    Filter,
    Trash2,
    Edit2,
    Search,
    Download,
    FileSpreadsheet,
    FileText,
    X,
    Sparkles,
    BarChart3,
    Coins,
    TrendingDown,
    TrendingUp,
    Landmark,
    CreditCard,
    Tag,
    Check,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import FormErrorSummary from '@/components/FormErrorSummary.vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import { useTransactionModal } from '@/composables/useTransactionModal';
import { jakartaDateKey } from '@/lib/dates';
import {
    destroy as transactionDestroy,
    exportMethod as transactionExport,
    index as transactionsIndex,
    update as transactionUpdate,
} from '@/routes/transactions';
import {
    excel as transactionExportExcel,
    pdf as transactionExportPdf,
} from '@/routes/transactions/export';
import type { User } from '@/types/auth';
import type {
    Transaction,
    Category,
    Wallet,
    SavingsMovement,
    InvestmentTransaction,
} from '@/types/finance';

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: any[];
        total: number;
        next_page_url?: string | null;
    };
    filters: {
        search?: string;
        scope?: string;
        type?: string;
        category_id?: string;
        wallet_id?: string;
        start_date?: string;
        end_date?: string;
    };
    wallets?: Wallet[];
    categories?: Category[];
    savingsMovements?: SavingsMovement[];
    savingsPagination?: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    investmentMovements?: {
        data: InvestmentTransaction[];
        links?: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    auth: {
        user: User;
    };
}>();

const { isOpen: isDrawerOpen } = useTransactionModal();

const isEditModalOpen = ref(false);
const editingTransaction = ref<Transaction | null>(null);
const transactionToDelete = ref<Transaction | null>(null);
const isDeleting = ref(false);
const showFilters = ref(false);
const showExports = ref(false);
const sourceWallets = computed(() =>
    (props.wallets || []).filter(
        (wallet) =>
            wallet.is_active !== false &&
            (wallet.type === 'joint' || wallet.user_id === props.auth.user.id),
    ),
);
const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    () => isEditModalOpen.value,
    () => (isEditModalOpen.value = false),
);

const search = ref(props.filters?.search || '');
const selectedScope = ref(props.filters?.scope || '');
const selectedType = ref(props.filters?.type || '');
const selectedWalletId = ref(props.filters?.wallet_id || '');
const selectedCategoryId = ref(props.filters?.category_id || '');
const selectedStartDate = ref(props.filters?.start_date || '');
const selectedEndDate = ref(props.filters?.end_date || '');
const displayedTransactions = ref<Transaction[]>([...props.transactions.data]);
const isLoadingMoreTransactions = ref(false);

watch(
    () => props.transactions,
    (transactions) => {
        displayedTransactions.value = [...transactions.data];
    },
);

const groupedTransactions = computed(() => {
    const groups = new Map<string, Transaction[]>();

    displayedTransactions.value.forEach((transaction) => {
        const dateKey = jakartaDateKey(transaction.transaction_date);
        const transactions = groups.get(dateKey) || [];
        transactions.push(transaction);
        groups.set(dateKey, transactions);
    });

    return Array.from(groups, ([date, transactions]) => ({
        date,
        transactions,
    }));
});

function formatDateHeading(date: string): string {
    const parsedDate = new Date(`${date}T00:00:00+07:00`);
    const today = jakartaDateKey();
    const yesterday = jakartaDateKey(
        new Date(new Date(`${today}T00:00:00+07:00`).getTime() - 86400000),
    );

    if (date === today) {
        return 'Hari ini';
    }

    if (date === yesterday) {
        return 'Kemarin';
    }

    return parsedDate.toLocaleDateString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function investmentWalletMutation(movement: InvestmentTransaction): number {
    const grossAmount = Number(movement.gross_amount);
    const feeAmount = Number(movement.fee_amount);

    return movement.type === 'buy'
        ? grossAmount + feeAmount
        : Math.max(0, grossAmount - feeAmount);
}

function investmentQuantity(value: number | string): string {
    return Number(value).toLocaleString('id-ID', {
        maximumFractionDigits: 8,
    });
}

function money(value: number | string): string {
    return `Rp ${Number(value).toLocaleString('id-ID', {
        maximumFractionDigits: 2,
    })}`;
}

const editForm = useForm({
    title: '',
    amount: '' as string | number,
    type: 'expense' as 'expense' | 'income' | 'transfer',
    scope: 'personal' as 'personal' | 'shared',
    wallet_id: null as number | null,
    to_wallet_id: null as number | null,
    category_id: null as number | null,
    transaction_date: '',
    notes: '',
    fee_amount: 0 as string | number,
});

function applyFilters() {
    router.get(
        transactionsIndex.url(),
        {
            search: search.value || undefined,
            scope: selectedScope.value || undefined,
            type: selectedType.value || undefined,
            wallet_id: selectedWalletId.value || undefined,
            category_id: selectedCategoryId.value || undefined,
            start_date: selectedStartDate.value || undefined,
            end_date: selectedEndDate.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetFilters() {
    search.value = '';
    selectedScope.value = '';
    selectedType.value = '';
    selectedWalletId.value = '';
    selectedCategoryId.value = '';
    selectedStartDate.value = '';
    selectedEndDate.value = '';
    applyFilters();
}

function loadMoreTransactions(): void {
    const nextPageUrl = props.transactions.next_page_url;

    if (!nextPageUrl || isLoadingMoreTransactions.value) {
        return;
    }

    router.get(
        nextPageUrl,
        { cumulative: true },
        {
            only: ['transactions'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => (isLoadingMoreTransactions.value = true),
            onFinish: () => (isLoadingMoreTransactions.value = false),
        },
    );
}

function openEditModal(tx: Transaction) {
    editForm.clearErrors();
    editingTransaction.value = tx;
    editForm.title = tx.title || '';
    editForm.amount = tx.amount;
    editForm.type = tx.type;
    editForm.scope = tx.scope;
    editForm.wallet_id = tx.wallet_id;
    editForm.to_wallet_id = tx.to_wallet_id || null;
    editForm.category_id = tx.category_id || null;
    editForm.transaction_date = tx.transaction_date
        ? jakartaDateKey(tx.transaction_date)
        : '';
    editForm.notes = tx.notes || '';
    editForm.fee_amount = tx.fee_amount || 0;
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!editingTransaction.value) {
        return;
    }

    editForm.put(transactionUpdate.url(editingTransaction.value.id), {
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingTransaction.value = null;
        },
    });
}

function deleteTransaction(transaction: Transaction): void {
    transactionToDelete.value = transaction;
}

function confirmDeleteTransaction(): void {
    if (!transactionToDelete.value) {
        return;
    }

    router.delete(transactionDestroy.url(transactionToDelete.value.id), {
        preserveScroll: true,
        replace: true,
        onStart: () => (isDeleting.value = true),
        onSuccess: () => (transactionToDelete.value = null),
        onFinish: () => (isDeleting.value = false),
    });
}

const exportQuery = computed(() => ({
    search: search.value || undefined,
    scope: selectedScope.value || undefined,
    type: selectedType.value || undefined,
    wallet_id: selectedWalletId.value || undefined,
    category_id: selectedCategoryId.value || undefined,
    start_date: selectedStartDate.value || undefined,
    end_date: selectedEndDate.value || undefined,
}));

function exportCsv() {
    window.location.href = transactionExport.url({
        query: {
            ...exportQuery.value,
        },
    });
}

function exportExcel() {
    window.location.href = transactionExportExcel.url({
        query: { ...exportQuery.value },
    });
}

function exportPdf() {
    window.open(
        transactionExportPdf.url({
            query: { ...exportQuery.value, print: 1 },
        }),
        '_blank',
        'noopener,noreferrer',
    );
}
</script>

<template>
    <Head title="Riwayat Transaksi - Couple Finance" />

    <div class="mx-auto max-w-4xl space-y-4 sm:space-y-5">
        <PageHeader title="Riwayat">
            <button
                type="button"
                @click="showExports = !showExports"
                class="inline-flex min-h-11 items-center gap-1.5 rounded-2xl px-2 text-xs font-bold text-indigo-800 transition-colors hover:bg-indigo-50 sm:border sm:border-slate-200 sm:bg-white sm:px-3.5 sm:shadow-sm dark:text-indigo-300 dark:hover:bg-indigo-950/40 sm:dark:border-zinc-800 sm:dark:bg-zinc-900"
                :aria-expanded="showExports"
            >
                <span>Ekspor</span>
                <Download class="h-4 w-4" />
            </button>

            <button
                type="button"
                @click="isDrawerOpen = true"
                class="hidden min-h-11 items-center gap-1.5 rounded-2xl bg-indigo-900 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-900/15 transition-all hover:bg-indigo-800 sm:inline-flex dark:bg-indigo-500 dark:hover:bg-indigo-400"
            >
                <Plus class="h-4 w-4" /> Catat Transaksi
            </button>
        </PageHeader>

        <div
            v-if="showExports"
            class="grid grid-cols-3 gap-2 rounded-[1.5rem] border border-slate-200/80 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
            <button
                type="button"
                @click="exportExcel"
                class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-2xl bg-emerald-50 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300"
            >
                <FileSpreadsheet class="h-5 w-5" />
                Excel
            </button>
            <button
                type="button"
                @click="exportPdf"
                class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-2xl bg-rose-50 text-xs font-bold text-rose-600 transition hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300"
            >
                <FileText class="h-5 w-5" />
                Laporan PDF
            </button>
            <button
                type="button"
                @click="exportCsv"
                class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-2xl bg-indigo-50 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300"
            >
                <Download class="h-5 w-5" />
                CSV
            </button>
        </div>

        <!-- Search & Filter Bar -->
        <div
            data-tour="history-filter"
            class="space-y-3 rounded-[1.5rem] border border-slate-200/80 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400"
                    />
                    <input
                        v-model="search"
                        aria-label="Cari transaksi"
                        @keydown.enter="applyFilters"
                        type="text"
                        placeholder="Cari transaksi, catatan, kategori..."
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pr-4 pl-10 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                </div>

                <button
                    type="button"
                    @click="showFilters = !showFilters"
                    class="flex min-h-11 items-center gap-1.5 rounded-2xl border px-3 py-2 text-xs font-semibold transition-colors"
                    :class="
                        showFilters
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400'
                            : 'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                    "
                >
                    <Filter class="h-3.5 w-3.5" />
                    <span>Filter</span>
                </button>
            </div>

            <!-- Expanded Filters -->
            <div
                v-if="showFilters"
                class="grid grid-cols-1 gap-2.5 border-t border-zinc-100 pt-2 text-xs sm:grid-cols-3 dark:border-zinc-800"
            >
                <!-- Scope Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Cakupan</label
                    >
                    <Select
                        :model-value="selectedScope || 'all'"
                        @update:model-value="
                            (value) => {
                                selectedScope =
                                    value === 'all' ? '' : String(value);
                                applyFilters();
                            }
                        "
                    >
                        <SelectTrigger
                            aria-label="Cakupan"
                            class="min-h-11 w-full rounded-xl"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Cakupan</SelectItem>
                            <SelectItem value="shared">Bersama</SelectItem>
                            <SelectItem value="personal">Pribadi</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Mulai Tanggal</label
                    >
                    <input
                        v-model="selectedStartDate"
                        @change="applyFilters"
                        type="date"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Sampai Tanggal</label
                    >
                    <input
                        v-model="selectedEndDate"
                        @change="applyFilters"
                        type="date"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <!-- Type Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Tipe Transaksi</label
                    >
                    <Select
                        :model-value="selectedType || 'all'"
                        @update:model-value="
                            (value) => {
                                selectedType =
                                    value === 'all' ? '' : String(value);
                                applyFilters();
                            }
                        "
                    >
                        <SelectTrigger
                            aria-label="Tipe transaksi"
                            class="min-h-11 w-full rounded-xl"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Tipe</SelectItem>
                            <SelectItem value="expense">Pengeluaran</SelectItem>
                            <SelectItem value="income">Pemasukan</SelectItem>
                            <SelectItem value="transfer">Transfer</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Wallet Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Dompet</label
                    >
                    <Select
                        :model-value="selectedWalletId || 'all'"
                        @update:model-value="
                            (value) => {
                                selectedWalletId =
                                    value === 'all' ? '' : String(value);
                                applyFilters();
                            }
                        "
                    >
                        <SelectTrigger
                            aria-label="Dompet"
                            class="min-h-11 w-full rounded-xl"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Dompet</SelectItem>
                            <SelectItem
                                v-for="w in wallets"
                                :key="w.id"
                                :value="String(w.id)"
                            >
                                {{ w.name }} ·
                                {{
                                    w.type === 'joint'
                                        ? 'Bersama'
                                        : w.user?.nickname ||
                                          w.user?.name ||
                                          'Tanpa pemilik'
                                }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Kategori</label
                    >
                    <Select
                        :model-value="selectedCategoryId || 'all'"
                        @update:model-value="
                            (value) => {
                                selectedCategoryId =
                                    value === 'all' ? '' : String(value);
                                applyFilters();
                            }
                        "
                    >
                        <SelectTrigger
                            aria-label="Kategori"
                            class="min-h-11 w-full rounded-xl"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Kategori</SelectItem>
                            <SelectItem
                                v-for="c in categories"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div
                    class="flex items-center justify-between gap-2 pt-1 sm:col-span-3"
                >
                    <button
                        type="button"
                        @click="exportCsv"
                        class="inline-flex min-h-11 items-center gap-1 text-xs font-semibold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200"
                    >
                        <Download class="h-3.5 w-3.5" /> CSV
                    </button>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="text-xs text-zinc-500 underline hover:text-zinc-800 dark:hover:text-zinc-200"
                    >
                        Reset Filter
                    </button>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="rounded-xl bg-indigo-600 px-3 py-1 text-xs font-bold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Terapkan
                    </button>
                </div>
            </div>
        </div>

        <section
            v-if="investmentMovements?.data.length"
            class="rounded-3xl border border-violet-200/80 bg-violet-50/60 p-4 shadow-sm dark:border-violet-900/60 dark:bg-violet-950/20"
        >
            <div class="mb-3 flex items-start gap-2">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white"
                >
                    <BarChart3 class="h-4 w-4" />
                </div>
                <div>
                    <h2
                        class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Aktivitas Investasi
                    </h2>
                    <p
                        class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400"
                    >
                        Pembelian dan penjualan aset tercatat sebagai mutasi,
                        bukan pengeluaran atau pemasukan harian.
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <div
                    v-for="movement in investmentMovements.data"
                    :key="movement.id"
                    class="flex items-start justify-between gap-3 rounded-2xl border border-violet-100 bg-white/90 p-3 dark:border-violet-900/50 dark:bg-zinc-900/80"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="
                                    movement.type === 'buy'
                                        ? 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300'
                                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                                "
                            >
                                {{ movement.type === 'buy' ? 'Beli' : 'Jual' }}
                            </span>
                            <p
                                class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100"
                            >
                                {{ movement.investment?.name || 'Investasi' }}
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ movement.wallet?.name || 'Dompet' }} ·
                            {{ investmentQuantity(movement.quantity) }} unit ·
                            {{
                                movement.user?.nickname ||
                                movement.user?.name ||
                                'Pengguna'
                            }}
                        </p>
                        <p
                            v-if="Number(movement.fee_amount) > 0"
                            class="mt-0.5 text-xs text-zinc-400"
                        >
                            Termasuk admin
                            {{ money(movement.fee_amount) }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <strong
                            class="text-xs font-extrabold"
                            :class="
                                movement.type === 'buy'
                                    ? 'text-zinc-900 dark:text-zinc-100'
                                    : 'text-emerald-700 dark:text-emerald-400'
                            "
                        >
                            {{ movement.type === 'buy' ? '-' : '+'
                            }}{{ money(investmentWalletMutation(movement)) }}
                        </strong>
                        <p class="mt-0.5 text-xs text-zinc-400">
                            {{
                                new Date(
                                    movement.transaction_date,
                                ).toLocaleDateString('id-ID', {
                                    timeZone: 'Asia/Jakarta',
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric',
                                })
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="(investmentMovements.links?.length || 0) > 1"
                class="mt-3 flex justify-center gap-2"
            >
                <Link
                    v-for="link in investmentMovements.links || []"
                    :key="link.label"
                    :href="link.url || '#'"
                    preserve-scroll
                    preserve-state
                    replace
                    class="rounded-xl border border-violet-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-violet-700 transition-colors hover:bg-violet-100 dark:border-violet-800 dark:bg-zinc-900 dark:text-violet-300"
                    :class="!link.url ? 'pointer-events-none opacity-40' : ''"
                >
                    <span v-html="link.label" />
                </Link>
            </div>
        </section>

        <section
            v-if="savingsMovements?.length"
            class="rounded-3xl border border-emerald-200/80 bg-emerald-50/60 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20"
        >
            <div class="mb-3 flex items-start gap-2">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white"
                >
                    <Coins class="h-4 w-4" />
                </div>
                <div>
                    <h2
                        class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Mutasi ke Tabungan
                    </h2>
                    <p
                        class="text-[11px] leading-relaxed text-zinc-500 dark:text-zinc-400"
                    >
                        Ini memindahkan saldo dompet ke target tabungan, bukan
                        pengeluaran.
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <div
                    v-for="movement in savingsMovements"
                    :key="movement.id"
                    class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-100 bg-white/90 p-3 dark:border-emerald-900/50 dark:bg-zinc-900/80"
                >
                    <div class="min-w-0">
                        <p
                            class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            {{ movement.wallet?.name || 'Dana di luar dompet' }}
                            → {{ movement.goal?.name || 'Tabungan' }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{
                                movement.user?.nickname ||
                                movement.user?.name ||
                                'Pengguna'
                            }}
                            ·
                            {{
                                new Date(
                                    movement.contributed_at,
                                ).toLocaleDateString('id-ID', {
                                    timeZone: 'Asia/Jakarta',
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric',
                                })
                            }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-xs font-extrabold text-emerald-700 dark:text-emerald-400"
                    >
                        Rp {{ Number(movement.amount).toLocaleString('id-ID') }}
                    </span>
                </div>
            </div>
        </section>

        <nav
            v-if="(savingsPagination?.length || 0) > 3"
            aria-label="Halaman setoran tabungan"
            class="flex flex-wrap gap-2"
        >
            <Link
                v-for="link in savingsPagination"
                :key="link.label"
                :href="link.url || '#'"
                preserve-scroll
                replace
                :aria-current="link.active ? 'page' : undefined"
                class="rounded-xl border px-3 py-2 text-sm"
                :class="{
                    'pointer-events-none opacity-40': !link.url,
                    'bg-emerald-100 text-emerald-900': link.active,
                }"
            >
                <span v-html="link.label" />
            </Link>
        </nav>

        <!-- Transactions Feed -->
        <div class="space-y-5">
            <section
                v-for="group in groupedTransactions"
                :key="group.date"
                class="space-y-2"
            >
                <h2
                    class="px-1 text-xs font-bold text-slate-500 dark:text-zinc-400"
                >
                    {{ formatDateHeading(group.date) }}
                </h2>
                <div
                    class="divide-y divide-slate-100 overflow-hidden rounded-[1.5rem] border border-slate-200/80 bg-white shadow-sm dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div
                        v-for="tx in group.transactions"
                        :key="tx.id"
                        class="flex items-start gap-3 p-4 transition-colors hover:bg-slate-50/80 dark:hover:bg-zinc-800/40"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-sm"
                                :style="{
                                    backgroundColor:
                                        tx.category?.color ||
                                        (tx.type === 'income'
                                            ? '#10B981'
                                            : '#6366F1'),
                                }"
                            >
                                <TrendingDown
                                    v-if="tx.type === 'expense'"
                                    class="h-5 w-5"
                                />
                                <TrendingUp
                                    v-else-if="tx.type === 'income'"
                                    class="h-5 w-5"
                                />
                                <ArrowRightLeft v-else class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3
                                    class="text-sm font-bold break-words text-zinc-900 dark:text-zinc-100"
                                >
                                    {{
                                        tx.title ||
                                        tx.category?.name ||
                                        'Transaksi'
                                    }}
                                </h3>
                                <div
                                    class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400"
                                >
                                    <span>{{
                                        tx.wallet?.name ||
                                        tx.emergency_savings_goal?.name ||
                                        'Dana darurat'
                                    }}</span>
                                    <span v-if="tx.to_wallet"
                                        >&rarr; {{ tx.to_wallet.name }}</span
                                    >
                                    <span>•</span>
                                    <span>{{
                                        tx.user?.nickname ||
                                        tx.user?.name?.split(' ')[0]
                                    }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-bold"
                                        :class="
                                            tx.scope === 'shared'
                                                ? 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400'
                                                : 'bg-indigo-500/10 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
                                        "
                                    >
                                        {{
                                            tx.scope === 'shared'
                                                ? 'Bersama'
                                                : 'Pribadi'
                                        }}
                                    </span>
                                    <span
                                        v-if="tx.source_type"
                                        class="rounded-full bg-indigo-500/10 px-2 py-0.5 text-xs font-bold text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                                    >
                                        Terhubung
                                        {{
                                            tx.source_type === 'subscription'
                                                ? 'Langganan'
                                                : tx.source_type === 'wishlist'
                                                  ? 'Wishlist'
                                                  : 'Anggaran'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <div class="shrink-0 text-right">
                                <span
                                    class="block text-sm font-extrabold whitespace-nowrap"
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
                                        Number(tx.amount).toLocaleString(
                                            'id-ID',
                                        )
                                    }}</span
                                >
                                <p
                                    class="text-xs whitespace-nowrap text-zinc-400"
                                >
                                    <span
                                        v-if="
                                            tx.type === 'transfer' &&
                                            Number(tx.fee_amount) > 0
                                        "
                                    >
                                        Admin Rp
                                        {{
                                            Number(
                                                tx.fee_amount,
                                            ).toLocaleString('id-ID')
                                        }}
                                        •
                                    </span>
                                    {{
                                        new Date(
                                            tx.transaction_date,
                                        ).toLocaleDateString('id-ID', {
                                            timeZone: 'Asia/Jakarta',
                                            day: 'numeric',
                                            month: 'short',
                                        })
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="tx.user_id === auth.user.id"
                                class="flex items-center gap-0.5"
                            >
                                <button
                                    v-if="!tx.source_type"
                                    type="button"
                                    @click="openEditModal(tx)"
                                    class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                    :aria-label="`Edit transaksi ${tx.title || 'tanpa judul'}`"
                                    :title="`Edit transaksi ${tx.title || 'tanpa judul'}`"
                                >
                                    <Edit2 class="h-4 w-4" />
                                </button>

                                <button
                                    type="button"
                                    @click="deleteTransaction(tx)"
                                    class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                                    :aria-label="`Hapus transaksi ${tx.title || 'tanpa judul'}`"
                                    :title="`Hapus transaksi ${tx.title || 'tanpa judul'}`"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div
                v-if="displayedTransactions.length === 0"
                class="p-10 text-center text-xs text-zinc-500"
            >
                Tidak ada transaksi yang cocok dengan filter.
            </div>
        </div>

        <!-- Load More Transactions -->
        <div v-if="transactions.next_page_url" class="flex justify-center pt-2">
            <button
                type="button"
                :disabled="isLoadingMoreTransactions"
                class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-indigo-200 bg-indigo-50 px-4 text-xs font-bold text-indigo-700 transition-colors hover:bg-indigo-100 disabled:cursor-wait disabled:opacity-60 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-950/70"
                @click="loadMoreTransactions"
            >
                {{
                    isLoadingMoreTransactions
                        ? 'Memuat transaksi…'
                        : 'Muat transaksi lainnya'
                }}
            </button>
        </div>

        <!-- Edit Transaction Modal -->
        <div
            v-if="isEditModalOpen && editingTransaction"
            @click.self="isEditModalOpen = false"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                @keydown="handleDialogKeydown"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-transaction-title"
                tabindex="-1"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="edit-transaction-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Edit Transaksi
                    </h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup form edit transaksi"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <!-- Scope -->
                    <div v-if="editForm.type === 'expense'">
                        <label class="block text-xs font-medium text-zinc-500"
                            >Cakupan</label
                        >
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="editForm.scope = 'personal'"
                                class="rounded-xl border p-2 text-xs font-medium transition-all"
                                :class="
                                    editForm.scope === 'personal'
                                        ? 'border-indigo-600 bg-indigo-600/10 text-indigo-600'
                                        : 'border-zinc-200 dark:border-zinc-800'
                                "
                            >
                                Pribadi
                            </button>
                            <button
                                type="button"
                                @click="editForm.scope = 'shared'"
                                class="rounded-xl border p-2 text-xs font-medium transition-all"
                                :class="
                                    editForm.scope === 'shared'
                                        ? 'border-rose-500 bg-rose-500/10 text-rose-600'
                                        : 'border-zinc-200 dark:border-zinc-800'
                                "
                            >
                                Bersama
                            </button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Judul Transaksi</label
                        >
                        <input
                            v-model="editForm.title"
                            type="text"
                            placeholder="Contoh: Nonton Bioskop, Makan Malam"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nominal (Rp)</label
                        >
                        <CurrencyInput
                            v-model="editForm.amount"
                            required
                            min="1"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div v-if="editForm.type === 'transfer'">
                        <label class="block text-xs font-medium text-zinc-500"
                            >Biaya Admin (Rp)</label
                        >
                        <CurrencyInput
                            v-model="editForm.fee_amount"
                            min="0"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                        <p class="mt-1 text-[11px] text-zinc-500">
                            Dompet asal dipotong nominal transfer + biaya admin.
                        </p>
                    </div>

                    <!-- Visual Wallet Selector -->
                    <div class="space-y-1.5">
                        <label
                            class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                        >
                            Sumber Dompet / Rekening
                        </label>
                        <div
                            class="grid max-h-36 grid-cols-2 gap-2 overflow-y-auto pr-1"
                        >
                            <button
                                v-for="w in sourceWallets"
                                :key="w.id"
                                type="button"
                                @click="editForm.wallet_id = w.id"
                                :aria-pressed="editForm.wallet_id === w.id"
                                class="relative flex cursor-pointer flex-col justify-between rounded-2xl border p-2.5 text-left transition-all active:scale-[0.98]"
                                :class="[
                                    editForm.wallet_id === w.id
                                        ? 'border-indigo-600 bg-indigo-50/70 shadow-sm ring-2 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-950/40'
                                        : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900',
                                ]"
                            >
                                <div
                                    class="flex items-center justify-between gap-1"
                                >
                                    <div
                                        class="flex items-center gap-1.5 truncate"
                                    >
                                        <div
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-white"
                                            :style="{
                                                backgroundColor:
                                                    w.color || '#6366F1',
                                            }"
                                        >
                                            <Landmark
                                                v-if="w.wallet_type === 'bank'"
                                                class="h-3 w-3"
                                            />
                                            <Coins
                                                v-else-if="
                                                    w.wallet_type === 'cash'
                                                "
                                                class="h-3 w-3"
                                            />
                                            <CreditCard
                                                v-else
                                                class="h-3 w-3"
                                            />
                                        </div>
                                        <span
                                            class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100"
                                            >{{ w.name }}</span
                                        >
                                    </div>
                                    <Check
                                        v-if="editForm.wallet_id === w.id"
                                        class="h-3.5 w-3.5 stroke-[3] text-indigo-600 dark:text-indigo-400"
                                    />
                                </div>
                                <div
                                    class="mt-1 flex items-center justify-between text-xs"
                                >
                                    <span class="text-zinc-400">{{
                                        w.type === 'joint'
                                            ? 'Kas Bersama'
                                            : `Milik ${w.user?.nickname || w.user?.name || 'Pribadi'}`
                                    }}</span>
                                    <span
                                        class="font-bold text-zinc-700 dark:text-zinc-300"
                                        >Rp
                                        {{
                                            Number(w.balance).toLocaleString(
                                                'id-ID',
                                            )
                                        }}</span
                                    >
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Visual Category Selector -->
                    <div
                        v-if="editForm.type !== 'transfer'"
                        class="space-y-1.5"
                    >
                        <label
                            class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                        >
                            Pilih Kategori
                        </label>
                        <div
                            class="flex max-h-32 flex-wrap gap-1.5 overflow-y-auto p-1"
                        >
                            <button
                                type="button"
                                @click="editForm.category_id = null"
                                class="inline-flex items-center gap-1 rounded-xl border px-2.5 py-1 text-xs font-medium transition-all"
                                :class="
                                    editForm.category_id === null
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400'
                                        : 'border-zinc-200 bg-white text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'
                                "
                            >
                                <Tag class="h-3 w-3 text-zinc-400" />
                                <span>Tanpa Kategori</span>
                            </button>
                            <button
                                v-for="c in categories"
                                :key="c.id"
                                type="button"
                                @click="editForm.category_id = c.id"
                                class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs font-medium transition-all"
                                :class="[
                                    editForm.category_id === c.id
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-600 ring-1 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-950/40 dark:text-indigo-300'
                                        : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
                                ]"
                            >
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    :style="{
                                        backgroundColor: c.color || '#6366F1',
                                    }"
                                />
                                <span>{{ c.name }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Tanggal Transaksi</label
                        >
                        <input
                            v-model="editForm.transaction_date"
                            type="date"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Catatan</label
                        >
                        <input
                            v-model="editForm.notes"
                            type="text"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <FormErrorSummary :errors="editForm.errors" />

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md transition-all hover:bg-indigo-500"
                    >
                        <Sparkles class="mr-1 inline h-4 w-4" />
                        {{
                            editForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Perubahan'
                        }}
                    </button>
                </form>
            </div>
        </div>

        <ConfirmActionDialog
            :open="transactionToDelete !== null"
            title="Hapus transaksi?"
            description="Saldo dompet akan otomatis disesuaikan kembali. Tindakan ini tidak dapat dibatalkan."
            :processing="isDeleting"
            @update:open="transactionToDelete = null"
            @confirm="confirmDeleteTransaction"
        />
    </div>
</template>
