<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PieChart, Plus, AlertCircle, CheckCircle2, Trash2, X, Edit2, Sparkles } from '@lucide/vue';
import type { Category, Wallet } from '@/types/finance';
import type { User } from '@/types/auth';

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

const createForm = useForm({
    name: '',
    limit_amount: '' as string | number,
    category_id: props.categories[0]?.id || null,
    scope: 'shared' as 'shared' | 'personal',
});

const editForm = useForm({
    name: '',
    limit_amount: '' as string | number,
    category_id: null as number | null,
    scope: 'shared' as 'shared' | 'personal',
});

function openEditModal(budget: BudgetItem) {
    editingBudget.value = budget;
    editForm.name = budget.name;
    editForm.limit_amount = budget.limit_amount;
    editForm.category_id = budget.category_id || budget.category?.id || null;
    editForm.scope = (budget.scope as any) || 'shared';
    isEditModalOpen.value = true;
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
    if (!editingBudget.value) return;
    editForm.put(`/budgets/${editingBudget.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingBudget.value = null;
        },
    });
}

function deleteBudget(budget: BudgetItem) {
    if (confirm(`Hapus anggaran "${budget.name}"?`)) {
        router.delete(`/budgets/${budget.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Anggaran & Batas Pengeluaran - Couple Finance" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Anggaran Bulanan
                </h1>
                <p class="text-xs text-zinc-500">Kendalikan batas pengeluaran kencan & kebutuhan bersama</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
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
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                            :style="{ backgroundColor: b.category?.color || '#6366F1' }"
                        >
                            <PieChart class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                {{ b.name }}
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Kategori: {{ b.category?.name || 'Semua Pengeluaran' }}
                                • {{ b.scope === 'personal' ? 'Pribadi' : 'Bersama' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <span
                            v-if="b.is_overbudget"
                            class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                        >
                            <AlertCircle class="h-3 w-3" /> Melebihi Batas!
                        </span>

                        <button
                            type="button"
                            @click="openEditModal(b)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="Edit Anggaran"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="deleteBudget(b)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                            title="Hapus Anggaran"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Budget Progress -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span class="font-bold" :class="b.is_overbudget ? 'text-rose-600' : 'text-zinc-900 dark:text-zinc-100'">
                            Terpakai: Rp {{ Number(b.spent_amount).toLocaleString('id-ID') }}
                        </span>
                        <span class="text-zinc-500">
                            Batas: Rp {{ Number(b.limit_amount).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="b.is_overbudget ? 'bg-rose-500' : (b.percentage > 80 ? 'bg-amber-500' : 'bg-emerald-500')"
                            :style="{ width: `${b.percentage}%` }"
                        />
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-zinc-500">
                    <span>Sisa Kuota: <strong class="text-emerald-600 dark:text-emerald-400">Rp {{ Number(b.remaining_amount).toLocaleString('id-ID') }}</strong></span>
                    <span>{{ b.percentage }}% Terpakai</span>
                </div>
            </div>

            <div
                v-if="budgets.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800"
            >
                Belum ada anggaran bulanan. Buat batasan budget untuk menjaga keuangan tetap sehat!
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Pasang Anggaran Baru</h2>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nama Anggaran</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Makan & Kencan, Belanja Bulanan"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Batas Maksimal Bulanan (Rp)</label>
                        <input
                            v-model="createForm.limit_amount"
                            type="number"
                            placeholder="0"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Kategori Pengeluaran</label>
                        <select
                            v-model="createForm.category_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option :value="null">-- Semua Kategori Pengeluaran --</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Cakupan Anggaran</label>
                        <select
                            v-model="createForm.scope"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="shared">Pengeluaran Bersama Pasangan</option>
                            <option value="personal">Pengeluaran Pribadi Kamu</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        Simpan Anggaran
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingBudget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Anggaran</h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nama Anggaran</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Batas Maksimal Bulanan (Rp)</label>
                        <input
                            v-model="editForm.limit_amount"
                            type="number"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Kategori Pengeluaran</label>
                        <select
                            v-model="editForm.category_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option :value="null">-- Semua Kategori Pengeluaran --</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Cakupan Anggaran</label>
                        <select
                            v-model="editForm.scope"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="shared">Pengeluaran Bersama Pasangan</option>
                            <option value="personal">Pengeluaran Pribadi Kamu</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        <Sparkles class="h-4 w-4 inline mr-1" />
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
