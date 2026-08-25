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
    X,
    Sparkles,
    Coins,
    TrendingDown,
    TrendingUp,
    Landmark,
    CreditCard,
    Tag,
    Check,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import {
    destroy as transactionDestroy,
    exportMethod as transactionExport,
    index as transactionsIndex,
    update as transactionUpdate,
} from '@/routes/transactions';
import type { User } from '@/types/auth';
import type {
    Transaction,
    Category,
    Wallet,
    SavingsMovement,
} from '@/types/finance';

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: any[];
        total: number;
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
    auth: {
        user: User;
    };
}>();

const { isOpen: isDrawerOpen } = useTransactionModal();

const isEditModalOpen = ref(false);
const editingTransaction = ref<Transaction | null>(null);
const showFilters = ref(false);
const sourceWallets = computed(() =>
    (props.wallets || []).filter(
        (wallet) =>
            wallet.type === 'joint' || wallet.user_id === props.auth.user.id,
    ),
);

const search = ref(props.filters?.search || '');
const selectedScope = ref(props.filters?.scope || '');
const selectedType = ref(props.filters?.type || '');
const selectedWalletId = ref(props.filters?.wallet_id || '');
const selectedCategoryId = ref(props.filters?.category_id || '');

const editForm = useForm({
    title: '',
    amount: '' as string | number,
    type: 'expense' as 'expense' | 'income' | 'transfer',
    scope: 'shared' as 'personal' | 'shared',
    wallet_id: null as number | null,
    to_wallet_id: null as number | null,
    category_id: null as number | null,
    transaction_date: '',
    notes: '',
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
    applyFilters();
}

function openEditModal(tx: Transaction) {
    editingTransaction.value = tx;
    editForm.title = tx.title || '';
    editForm.amount = tx.amount;
    editForm.type = tx.type as any;
    editForm.scope = tx.scope as any;
    editForm.wallet_id = tx.wallet_id;
    editForm.to_wallet_id = tx.to_wallet_id || null;
    editForm.category_id = tx.category_id || null;
    editForm.transaction_date = tx.transaction_date
        ? tx.transaction_date.slice(0, 10)
        : '';
    editForm.notes = tx.notes || '';
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!editingTransaction.value) {
        return;
    }

    editForm.put(transactionUpdate.url(editingTransaction.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingTransaction.value = null;
        },
    });
}

function deleteTransaction(id: number) {
    if (
        confirm(
            'Yakin ingin menghapus transaksi ini? Saldo dompet akan otomatis disesuaikan kembali.',
        )
    ) {
        router.delete(transactionDestroy.url(id), {
            preserveScroll: true,
        });
    }
}

function exportCsv() {
    window.location.href = transactionExport.url({
        query: {
            search: search.value || undefined,
            scope: selectedScope.value || undefined,
            type: selectedType.value || undefined,
            wallet_id: selectedWalletId.value || undefined,
            category_id: selectedCategoryId.value || undefined,
        },
    });
}
</script>

<template>
    <Head title="Riwayat Transaksi - Couple Finance" />

    <div class="space-y-6">
        <!-- Top Bar Action -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-base font-bold text-zinc-900 dark:text-zinc-100"
                >
                    Riwayat Transaksi
                </h1>
                <p class="text-xs text-zinc-500">
                    Daftar arus kas pribadi dan pengeluaran kencan bersama
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="exportCsv"
                    class="inline-flex min-h-11 items-center gap-1.5 rounded-2xl border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    title="Download Rekap CSV"
                >
                    <Download
                        class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                    />
                    <span>Export CSV</span>
                </button>

                <button
                    type="button"
                    @click="isDrawerOpen = true"
                    class="inline-flex min-h-11 items-center gap-1.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all hover:opacity-95"
                >
                    <Plus class="h-4 w-4" /> Catat Transaksi
                </button>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div
            class="space-y-3 rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
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
                        placeholder="Cari transaksi berdasarkan judul atau catatan..."
                        class="min-h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 py-2 pr-4 pl-10 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
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
                class="grid grid-cols-1 gap-2.5 border-t border-zinc-100 pt-2 text-xs sm:grid-cols-4 dark:border-zinc-800"
            >
                <!-- Scope Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Cakupan</label
                    >
                    <select
                        v-model="selectedScope"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua Cakupan</option>
                        <option value="shared">Kencan / Bersama</option>
                        <option value="personal">Pribadi</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Tipe Transaksi</label
                    >
                    <select
                        v-model="selectedType"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua Tipe</option>
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>

                <!-- Wallet Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Dompet</label
                    >
                    <select
                        v-model="selectedWalletId"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua Dompet</option>
                        <option v-for="w in wallets" :key="w.id" :value="w.id">
                            {{ w.name }} ·
                            {{
                                w.type === 'joint'
                                    ? 'Bersama'
                                    : w.user?.nickname ||
                                      w.user?.name ||
                                      'Tanpa pemilik'
                            }}
                        </option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label
                        class="mb-1 block text-[11px] font-semibold text-zinc-500"
                        >Kategori</label
                    >
                    <select
                        v-model="selectedCategoryId"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">Semua Kategori</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-1 sm:col-span-4">
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
                            {{
                                movement.wallet?.name || 'Dana di luar dompet'
                            }}
                            → {{ movement.goal?.name || 'Tabungan' }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-zinc-500">
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

        <!-- Transactions Feed -->
        <div
            class="divide-y divide-zinc-100 rounded-3xl border border-zinc-200/80 bg-white shadow-sm dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                v-for="tx in transactions.data"
                :key="tx.id"
                class="flex items-start gap-3 p-4 transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40"
            >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-sm"
                        :style="{
                            backgroundColor:
                                tx.category?.color ||
                                (tx.type === 'income' ? '#10B981' : '#6366F1'),
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
                            {{ tx.title || tx.category?.name || 'Transaksi' }}
                        </h3>
                        <div
                            class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400"
                        >
                            <span>{{ tx.wallet?.name }}</span>
                            <span v-if="tx.to_wallet"
                                >&rarr; {{ tx.to_wallet.name }}</span
                            >
                            <span>•</span>
                            <span>{{
                                tx.user?.nickname ||
                                tx.user?.name?.split(' ')[0]
                            }}</span>
                            <span
                                v-if="tx.scope === 'shared'"
                                class="py-0.2 rounded-full bg-rose-500/10 px-2 text-[10px] font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                            >
                                Kencan Bersama
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

                    <div
                        v-if="tx.user_id === auth.user.id"
                        class="flex items-center gap-0.5"
                    >
                        <button
                            type="button"
                            @click="openEditModal(tx)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            title="Edit Transaksi"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="deleteTransaction(tx.id)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Hapus Transaksi"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="transactions.data.length === 0"
                class="p-10 text-center text-xs text-zinc-500"
            >
                Tidak ada transaksi yang cocok dengan filter.
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="transactions.links && transactions.links.length > 3"
            class="flex justify-center gap-1 pt-2"
        >
            <Link
                v-for="link in transactions.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="[
                    link.active
                        ? 'bg-indigo-600 text-white'
                        : 'border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
            >
                <span v-html="link.label" />
            </Link>
        </div>

        <!-- Edit Transaction Modal -->
        <div
            v-if="isEditModalOpen && editingTransaction"
            @click.self="isEditModalOpen = false"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                @click.stop
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Edit Transaksi
                    </h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <!-- Scope -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Cakupan</label
                        >
                        <div class="mt-1 grid grid-cols-2 gap-2">
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
                                Kencan / Bersama
                            </button>
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
                        <input
                            v-model="editForm.amount"
                            type="number"
                            required
                            min="1"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
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
                            <div
                                v-for="w in sourceWallets"
                                :key="w.id"
                                @click="editForm.wallet_id = w.id"
                                class="relative flex cursor-pointer flex-col justify-between rounded-2xl border p-2.5 transition-all active:scale-[0.98]"
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
                                    class="mt-1 flex items-center justify-between text-[10px]"
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
                            </div>
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

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md transition-all hover:bg-indigo-500"
                    >
                        <Sparkles class="mr-1 inline h-4 w-4" />
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
