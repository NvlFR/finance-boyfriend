<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Repeat, Plus, Calendar, Check, Trash2, X, Wallet as WalletIcon, Edit2, Sparkles } from '@lucide/vue';
import type { Wallet, Category } from '@/types/finance';
import type { User } from '@/types/auth';

type SubscriptionItem = {
    id: number;
    name: string;
    amount: string | number;
    billing_cycle: string;
    next_billing_date: string;
    split_mode: string;
    color: string;
    is_active: boolean;
    paid_by_user?: User;
    wallet?: Wallet;
};

const props = defineProps<{
    subscriptions: SubscriptionItem[];
    wallets: Wallet[];
    categories?: Category[];
    partner?: User | null;
    total_monthly_cost: number;
    auth: {
        user: User;
    };
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingSub = ref<SubscriptionItem | null>(null);

const createForm = useForm({
    name: '',
    amount: '' as string | number,
    billing_cycle: 'monthly' as 'monthly' | 'yearly',
    next_billing_date: new Date().toISOString().slice(0, 10),
    split_mode: '50_50' as '50_50' | 'alternate' | 'single',
    wallet_id: props.wallets[0]?.id || null,
    color: '#6366F1',
});

const editForm = useForm({
    name: '',
    amount: '' as string | number,
    billing_cycle: 'monthly' as 'monthly' | 'yearly',
    next_billing_date: '',
    split_mode: '50_50' as '50_50' | 'alternate' | 'single',
    wallet_id: null as number | null,
    color: '#6366F1',
    is_active: true,
});

const colors = ['#6366F1', '#EC4899', '#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#14B8A6'];

function openEditModal(sub: SubscriptionItem) {
    editingSub.value = sub;
    editForm.name = sub.name;
    editForm.amount = sub.amount;
    editForm.billing_cycle = sub.billing_cycle as any;
    editForm.next_billing_date = sub.next_billing_date;
    editForm.split_mode = sub.split_mode as any;
    editForm.wallet_id = sub.wallet?.id || null;
    editForm.color = sub.color || '#6366F1';
    editForm.is_active = sub.is_active;
    isEditModalOpen.value = true;
}

function submitCreate() {
    createForm.post('/subscriptions', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function submitEdit() {
    if (!editingSub.value) return;
    editForm.put(`/subscriptions/${editingSub.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingSub.value = null;
        },
    });
}

function deleteSubscription(sub: SubscriptionItem) {
    if (confirm(`Hapus langganan "${sub.name}"?`)) {
        router.delete(`/subscriptions/${sub.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Langganan & Tagihan Rutin - Couple Finance" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Langganan & Tagihan Bersama
                </h1>
                <p class="text-xs text-zinc-500">Kelola Netflix, Spotify, Internet, dan tagihan rutin</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
            >
                <Plus class="h-4 w-4" /> Tambah Langganan
            </button>
        </div>

        <!-- Monthly Summary Banner -->
        <div class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-950 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800">
            <span class="text-xs font-medium uppercase tracking-wider text-indigo-300">Estimasi Beban Langganan Bulanan</span>
            <div class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight">
                Rp {{ Number(total_monthly_cost).toLocaleString('id-ID') }}
                <span class="text-xs font-normal text-zinc-400">/ bulan</span>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="space-y-3">
            <div
                v-for="sub in subscriptions"
                :key="sub.id"
                class="flex items-center justify-between rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                        :style="{ backgroundColor: sub.color || '#6366F1' }"
                    >
                        <Repeat class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ sub.name }}
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Jatuh tempo: {{ new Date(sub.next_billing_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                            • {{ sub.billing_cycle === 'monthly' ? 'Bulanan' : 'Tahunan' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            Rp {{ Number(sub.amount).toLocaleString('id-ID') }}
                        </span>
                        <div class="text-[10px] text-zinc-400">
                            {{ sub.split_mode === '50_50' ? 'Bagi 50:50' : 'Dibayar 1 Pihak' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="openEditModal(sub)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="Edit Langganan"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="deleteSubscription(sub)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                            title="Hapus Langganan"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="subscriptions.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800"
            >
                Belum ada tagihan rutin atau langganan yang dicatat.
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Tambah Langganan Baru</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Layanan</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Netflix Premium, Spotify Family, WiFi Indihome"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nominal Biaya (Rp)</label>
                        <input
                            v-model="createForm.amount"
                            type="number"
                            placeholder="0"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500">Siklus Tagihan</label>
                            <select
                                v-model="createForm.billing_cycle"
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            >
                                <option value="monthly">Bulanan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-500">Jatuh Tempo Berikutnya</label>
                            <input
                                v-model="createForm.next_billing_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Skema Pembagian</label>
                        <select
                            v-model="createForm.split_mode"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="50_50">Bagi Rata (50 : 50)</option>
                            <option value="single">Dibayar Sendiri Sepenuhnya</option>
                            <option value="alternate">Bergantian Tiap Bulan</option>
                        </select>
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
                                <Check v-if="createForm.color === c" class="h-3.5 w-3.5 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        Simpan Langganan
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingSub"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Langganan</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Layanan</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nominal Biaya (Rp)</label>
                        <input
                            v-model="editForm.amount"
                            type="number"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-500">Siklus Tagihan</label>
                            <select
                                v-model="editForm.billing_cycle"
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            >
                                <option value="monthly">Bulanan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-500">Jatuh Tempo Berikutnya</label>
                            <input
                                v-model="editForm.next_billing_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Skema Pembagian</label>
                        <select
                            v-model="editForm.split_mode"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="50_50">Bagi Rata (50 : 50)</option>
                            <option value="single">Dibayar Sendiri</option>
                            <option value="alternate">Bergantian</option>
                        </select>
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
                                <Check v-if="editForm.color === c" class="h-3.5 w-3.5 text-white" />
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
    </div>
</template>
