<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Target, Plus, Sparkles, CheckCircle2, Trophy, Coins, X, Edit2, Trash2 } from '@lucide/vue';
import type { Wallet, Category } from '@/types/finance';
import type { User } from '@/types/auth';

type Goal = {
    id: number;
    name: string;
    target_amount: string | number;
    current_amount: string | number;
    target_date: string | null;
    status: string;
    color: string;
    percentage: number;
    created_by_user?: User;
    contributions?: any[];
};

const props = defineProps<{
    goals: Goal[];
    wallets: Wallet[];
    categories?: Category[];
    partner?: User | null;
    total_saved: number;
    total_target: number;
    auth: {
        user: User;
    };
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isContributeModalOpen = ref(false);
const selectedGoal = ref<Goal | null>(null);
const editingGoal = ref<Goal | null>(null);

const createForm = useForm({
    name: '',
    target_amount: '' as string | number,
    target_date: '',
    color: '#6366F1',
});

const editForm = useForm({
    name: '',
    target_amount: '' as string | number,
    target_date: '',
    color: '#6366F1',
});

const contributeForm = useForm({
    amount: '' as string | number,
    wallet_id: props.wallets[0]?.id || null,
    notes: 'Setoran tabungan',
});

const colors = ['#6366F1', '#EC4899', '#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#14B8A6'];

function openContributeModal(goal: Goal) {
    selectedGoal.value = goal;
    contributeForm.amount = '';
    isContributeModalOpen.value = true;
}

function openEditModal(goal: Goal) {
    editingGoal.value = goal;
    editForm.name = goal.name;
    editForm.target_amount = goal.target_amount;
    editForm.target_date = goal.target_date || '';
    editForm.color = goal.color || '#6366F1';
    isEditModalOpen.value = true;
}

function submitCreate() {
    createForm.post('/goals', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function submitEdit() {
    if (!editingGoal.value) return;
    editForm.put(`/goals/${editingGoal.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingGoal.value = null;
        },
    });
}

function submitContribute() {
    if (!selectedGoal.value) return;
    contributeForm.post(`/goals/${selectedGoal.value.id}/contribute`, {
        preserveScroll: true,
        onSuccess: () => {
            contributeForm.reset();
            isContributeModalOpen.value = false;
        },
    });
}

function deleteGoal(goal: Goal) {
    if (confirm(`Hapus target tabungan "${goal.name}"?`)) {
        router.delete(`/goals/${goal.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Tabungan Bersama - Couple Finance" />

    <div class="space-y-6">
        <!-- Top Bar Action -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Tabungan Bersama (Goals)
                </h1>
                <p class="text-xs text-zinc-500">Rencanakan dana liburan, nikah, dan rumah impian</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-indigo-600 to-rose-500 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:opacity-90 transition-all"
            >
                <Plus class="h-4 w-4" /> Buat Target
            </button>
        </div>

        <!-- Summary Progress Banner -->
        <div class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-900/90 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wider text-indigo-300">Total Terkumpul</span>
                    <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Rp {{ Number(total_saved).toLocaleString('id-ID') }}
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-zinc-400">Total Target</span>
                    <p class="text-sm font-semibold text-zinc-200">
                        Rp {{ Number(total_target).toLocaleString('id-ID') }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex justify-between text-xs text-zinc-400 mb-1.5">
                    <span>Kemajuan Keseluruhan</span>
                    <span class="font-bold text-white">
                        {{ total_target > 0 ? Math.min(100, Math.round((total_saved / total_target) * 100)) : 0 }}%
                    </span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-white/10">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-rose-500 transition-all duration-500"
                        :style="{ width: `${total_target > 0 ? Math.min(100, Math.round((total_saved / total_target) * 100)) : 0}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- Goals Grid -->
        <div class="space-y-4">
            <div
                v-for="goal in goals"
                :key="goal.id"
                class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                            :style="{ backgroundColor: goal.color || '#6366F1' }"
                        >
                            <Target class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                {{ goal.name }}
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Target: {{ goal.target_date ? new Date(goal.target_date).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : 'Fleksibel' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span
                            v-if="goal.status === 'achieved'"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                        >
                            <Trophy class="h-3.5 w-3.5" /> Tercapai!
                        </span>

                        <button
                            type="button"
                            @click="openEditModal(goal)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="Edit Target"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="deleteGoal(goal)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                            title="Hapus Target"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">
                            Rp {{ Number(goal.current_amount).toLocaleString('id-ID') }}
                        </span>
                        <span class="text-zinc-500">
                            dari Rp {{ Number(goal.target_amount).toLocaleString('id-ID') }}
                        </span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{
                                width: `${Math.min(100, Math.round((Number(goal.current_amount) / Number(goal.target_amount)) * 100))}%`,
                                backgroundColor: goal.color || '#6366F1',
                            }"
                        />
                    </div>
                </div>

                <!-- Action Button -->
                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <span class="text-xs text-zinc-400">
                        {{ Math.min(100, Math.round((Number(goal.current_amount) / Number(goal.target_amount)) * 100)) }}% Terkumpul
                    </span>

                    <button
                        type="button"
                        @click="openContributeModal(goal)"
                        class="flex items-center gap-1 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition-colors"
                    >
                        <Coins class="h-3.5 w-3.5" /> + Setor Tabungan
                    </button>
                </div>
            </div>

            <div
                v-if="goals.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800"
            >
                Belum ada target tabungan bersama. Klik tombol "Buat Target" untuk memulai!
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Buat Target Tabungan Impian</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Impian</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Liburan ke Jepang, Dana Pernikahan"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Target Nominal (Rp)</label>
                        <input
                            v-model="createForm.target_amount"
                            type="number"
                            placeholder="0"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Target Tanggal (Opsional)</label>
                        <input
                            v-model="createForm.target_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Warna Aksen</label>
                        <div class="mt-2 flex gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="createForm.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                            >
                                <CheckCircle2 v-if="createForm.color === c" class="h-4 w-4 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        Simpan Impian
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingGoal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Target Tabungan</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Impian</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Target Nominal (Rp)</label>
                        <input
                            v-model="editForm.target_amount"
                            type="number"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Target Tanggal</label>
                        <input
                            v-model="editForm.target_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Warna Aksen</label>
                        <div class="mt-2 flex gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="editForm.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                            >
                                <CheckCircle2 v-if="editForm.color === c" class="h-4 w-4 text-white" />
                            </button>
                        </div>
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

        <!-- Contribute Modal -->
        <div
            v-if="isContributeModalOpen && selectedGoal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Setor Tabungan</h2>
                    <button
                        type="button"
                        @click="isContributeModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitContribute" class="mt-4 space-y-4">
                    <div class="rounded-2xl bg-indigo-500/10 p-3 text-xs text-indigo-700 dark:text-indigo-300">
                        Menabung untuk: <strong class="text-zinc-900 dark:text-zinc-100">{{ selectedGoal.name }}</strong>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nominal Setoran (Rp)</label>
                        <input
                            v-model="contributeForm.amount"
                            type="number"
                            placeholder="Contoh: 100000"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div v-if="wallets.length > 0">
                        <label class="block text-xs font-medium text-zinc-500">Potong dari Dompet (Opsional)</label>
                        <select
                            v-model="contributeForm.wallet_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option :value="null">-- Tanpa Potong Saldo Dompet --</option>
                            <option v-for="w in wallets" :key="w.id" :value="w.id">
                                {{ w.name }} (Saldo: Rp {{ Number(w.balance).toLocaleString('id-ID') }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Catatan / Pesan</label>
                        <input
                            v-model="contributeForm.notes"
                            type="text"
                            placeholder="Contoh: Setoran gajian bulan ini"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="contributeForm.processing || !contributeForm.amount"
                        class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 py-3 text-xs font-bold text-white shadow-md hover:opacity-90 transition-all"
                    >
                        Konfirmasi Setor
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
