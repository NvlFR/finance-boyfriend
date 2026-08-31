<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    PieChart,
    Plus,
    AlertCircle,
    Trash2,
    X,
    Edit2,
    Sparkles,
    ReceiptText,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import FormErrorSummary from '@/components/FormErrorSummary.vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import { useTransactionModal } from '@/composables/useTransactionModal';
import type { User } from '@/types/auth';
import type { Category, Wallet } from '@/types/finance';

type BudgetItem = {
    id: number;
    name: string;
    limit_amount: string | number;
    spent_amount: number;
    percentage: number;
    remaining_amount: number;
    is_overbudget: boolean;
    category_id?: number;
    scope?: string;
    period: 'daily' | 'monthly';
    category?: Category;
};

const props = defineProps<{
    budgets: BudgetItem[];
    categories: Category[];
    wallets?: Wallet[];
    partner?: User | null;
    auth: {
        user: User;
    };
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingBudget = ref<BudgetItem | null>(null);
const budgetToDelete = ref<BudgetItem | null>(null);
const isDeleting = ref(false);
const { openModalWithDefaults } = useTransactionModal();
const isAnyModalOpen = computed(
    () => isCreateModalOpen.value || isEditModalOpen.value,
);

function closeActiveModal(): void {
    isCreateModalOpen.value = false;
    isEditModalOpen.value = false;
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    () => isAnyModalOpen.value,
    closeActiveModal,
);

const createForm = useForm({
    name: '',
    limit_amount: '' as string | number,
    category_id: props.categories?.[0]?.id || null,
    period: 'daily' as 'daily' | 'monthly',
    scope: 'personal' as 'shared' | 'personal',
});

const editForm = useForm({
    name: '',
    limit_amount: '' as string | number,
    category_id: null as number | null,
    period: 'daily' as 'daily' | 'monthly',
    scope: 'personal' as 'shared' | 'personal',
});

function openEditModal(budget: BudgetItem) {
    editForm.clearErrors();
    editingBudget.value = budget;
    editForm.name = budget.name;
    editForm.limit_amount = budget.limit_amount;
    editForm.category_id = budget.category_id || budget.category?.id || null;
    editForm.period = budget.period || 'daily';
    editForm.scope = (budget.scope as 'shared' | 'personal') || 'personal';
    isEditModalOpen.value = true;
}

function openCreateModal(): void {
    createForm.clearErrors();
    isCreateModalOpen.value = true;
}

function submitCreate() {
    createForm.post('/budgets', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function submitEdit() {
    if (!editingBudget.value) {
        return;
    }

    editForm.put(`/budgets/${editingBudget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingBudget.value = null;
        },
    });
}

function deleteBudget(budget: BudgetItem) {
    budgetToDelete.value = budget;
}

function confirmDeleteBudget(): void {
    if (!budgetToDelete.value) {
        return;
    }

    router.delete(`/budgets/${budgetToDelete.value.id}`, {
        preserveScroll: true,
        onStart: () => (isDeleting.value = true),
        onSuccess: () => (budgetToDelete.value = null),
        onFinish: () => (isDeleting.value = false),
    });
}

function recordBudgetExpense(budget: BudgetItem) {
    openModalWithDefaults({
        type: 'expense',
        scope: budget.scope === 'personal' ? 'personal' : 'shared',
        title: budget.name,
        category_id: budget.category_id || budget.category?.id || null,
        source_type: 'budget',
        source_id: budget.id,
    });
}
</script>

<template>
    <Head title="Anggaran & Batas Pengeluaran - Couple Finance" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="min-w-0">
                <h1
                    class="text-base font-bold text-zinc-900 dark:text-zinc-100"
                >
                    Kebutuhan Harian & Bulanan
                </h1>
                <p class="text-xs text-zinc-500">
                    Pasang batas sederhana untuk kebutuhan pribadi atau bersama
                </p>
            </div>

            <button
                type="button"
                @click="openCreateModal"
                class="flex min-h-11 items-center gap-1.5 rounded-full bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-indigo-500"
            >
                <Plus class="h-4 w-4" /> Pasang Budget
            </button>
        </div>

        <!-- Budget Cards List -->
        <div class="space-y-4">
            <div
                v-for="b in budgets"
                :key="b.id"
                class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                            :style="{
                                backgroundColor: b.category?.color || '#6366F1',
                            }"
                        >
                            <PieChart class="h-5 w-5" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                            >
                                {{ b.name }}
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Kategori:
                                {{ b.category?.name || 'Semua Pengeluaran' }} •
                                {{
                                    b.period === 'daily' ? 'Harian' : 'Bulanan'
                                }}
                                •
                                {{
                                    b.scope === 'personal'
                                        ? 'Pribadi'
                                        : 'Bersama'
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex w-full flex-wrap items-center justify-end gap-1 sm:w-auto"
                    >
                        <button
                            type="button"
                            @click="recordBudgetExpense(b)"
                            class="flex min-h-11 items-center gap-1.5 rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white transition-colors hover:bg-emerald-500"
                            title="Catat pengeluaran untuk anggaran ini"
                        >
                            <ReceiptText class="h-4 w-4" /> Catat
                        </button>
                        <span
                            v-if="b.is_overbudget"
                            class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                        >
                            <AlertCircle class="h-3 w-3" /> Melebihi Batas!
                        </span>

                        <button
                            type="button"
                            @click="openEditModal(b)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            title="Edit Anggaran"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="deleteBudget(b)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Hapus Anggaran"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Budget Progress -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span
                            class="font-bold"
                            :class="
                                b.is_overbudget
                                    ? 'text-rose-600'
                                    : 'text-zinc-900 dark:text-zinc-100'
                            "
                        >
                            Terpakai: Rp
                            {{ Number(b.spent_amount).toLocaleString('id-ID') }}
                        </span>
                        <span class="text-zinc-500">
                            Batas: Rp
                            {{ Number(b.limit_amount).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div
                        class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="
                                b.is_overbudget
                                    ? 'bg-rose-500'
                                    : b.percentage > 80
                                      ? 'bg-amber-500'
                                      : 'bg-emerald-500'
                            "
                            :style="{ width: `${b.percentage}%` }"
                        />
                    </div>
                </div>

                <div
                    class="mt-3 flex items-center justify-between text-xs text-zinc-500"
                >
                    <span
                        >{{
                            b.is_overbudget ? 'Melebihi Batas:' : 'Sisa Kuota:'
                        }}
                        <strong
                            :class="
                                b.is_overbudget
                                    ? 'text-rose-600 dark:text-rose-400'
                                    : 'text-emerald-600 dark:text-emerald-400'
                            "
                            >Rp
                            {{
                                Number(
                                    b.is_overbudget
                                        ? b.spent_amount -
                                              Number(b.limit_amount)
                                        : b.remaining_amount,
                                ).toLocaleString('id-ID')
                            }}</strong
                        ></span
                    >
                    <span>{{ b.percentage }}% Terpakai</span>
                </div>
            </div>

            <div
                v-if="budgets.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800"
            >
                Belum ada batas kebutuhan. Mulai dari kebutuhan harian yang
                paling sering keluar.
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            @click.self="closeActiveModal"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                @keydown="handleDialogKeydown"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-budget-title"
                tabindex="-1"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="create-budget-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Pasang Anggaran Baru
                    </h2>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup form anggaran"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nama Anggaran</label
                        >
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Uang Makan, Belanja Bulanan"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Periode Kebutuhan</label
                        >
                        <select
                            v-model="createForm.period"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Batas Maksimal
                            {{
                                createForm.period === 'daily'
                                    ? 'Harian'
                                    : 'Bulanan'
                            }}
                            (Rp)</label
                        >
                        <input
                            v-model="createForm.limit_amount"
                            type="number"
                            placeholder="0"
                            required
                            min="1"
                            step="1"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Kategori Pengeluaran</label
                        >
                        <select
                            v-model="createForm.category_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option :value="null">
                                -- Semua Kategori Pengeluaran --
                            </option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Cakupan Anggaran</label
                        >
                        <select
                            v-model="createForm.scope"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="personal">
                                Pengeluaran Pribadi Kamu
                            </option>
                            <option value="shared">
                                Pengeluaran Bersama Pasangan
                            </option>
                        </select>
                    </div>

                    <FormErrorSummary :errors="createForm.errors" />

                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md transition-all hover:bg-indigo-500"
                    >
                        {{
                            createForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Anggaran'
                        }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingBudget"
            @click.self="closeActiveModal"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                @keydown="handleDialogKeydown"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-budget-title"
                tabindex="-1"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="edit-budget-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Edit Anggaran
                    </h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup form edit anggaran"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nama Anggaran</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Periode Kebutuhan</label
                        >
                        <select
                            v-model="editForm.period"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Batas Maksimal
                            {{
                                editForm.period === 'daily'
                                    ? 'Harian'
                                    : 'Bulanan'
                            }}
                            (Rp)</label
                        >
                        <input
                            v-model="editForm.limit_amount"
                            type="number"
                            required
                            min="1"
                            step="1"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Kategori Pengeluaran</label
                        >
                        <select
                            v-model="editForm.category_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option :value="null">
                                -- Semua Kategori Pengeluaran --
                            </option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Cakupan Anggaran</label
                        >
                        <select
                            v-model="editForm.scope"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="personal">
                                Pengeluaran Pribadi Kamu
                            </option>
                            <option value="shared">
                                Pengeluaran Bersama Pasangan
                            </option>
                        </select>
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
            :open="budgetToDelete !== null"
            title="Hapus anggaran?"
            :description="`Anggaran ${budgetToDelete?.name || ''} akan dihapus. Transaksi yang sudah dicatat tidak ikut terhapus.`"
            :processing="isDeleting"
            @update:open="budgetToDelete = null"
            @confirm="confirmDeleteBudget"
        />
    </div>
</template>
