<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Repeat,
    Plus,
    Check,
    Trash2,
    X,
    Edit2,
    Sparkles,
    CreditCard,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import FormErrorSummary from '@/components/FormErrorSummary.vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import { useTransactionModal } from '@/composables/useTransactionModal';
import type { User } from '@/types/auth';
import type { Wallet, Category } from '@/types/finance';

type SubscriptionItem = {
    id: number;
    name: string;
    amount: string | number;
    billing_cycle: string;
    next_billing_date: string;
    last_paid_at?: string | null;
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
const subscriptionToDelete = ref<SubscriptionItem | null>(null);
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
    amount: '' as string | number,
    billing_cycle: 'monthly' as 'monthly' | 'yearly',
    next_billing_date: new Date().toISOString().slice(0, 10),
    split_mode: '50_50' as '50_50' | 'alternate' | 'single',
    wallet_id: props.wallets?.[0]?.id || null,
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

const colors = [
    '#6366F1',
    '#EC4899',
    '#10B981',
    '#F59E0B',
    '#3B82F6',
    '#8B5CF6',
    '#14B8A6',
];

function openEditModal(sub: SubscriptionItem) {
    editForm.clearErrors();
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

function openCreateModal(): void {
    createForm.clearErrors();
    isCreateModalOpen.value = true;
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
    if (!editingSub.value) {
        return;
    }

    editForm.put(`/subscriptions/${editingSub.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingSub.value = null;
        },
    });
}

function deleteSubscription(sub: SubscriptionItem) {
    subscriptionToDelete.value = sub;
}

function confirmDeleteSubscription(): void {
    if (!subscriptionToDelete.value) {
        return;
    }

    router.delete(`/subscriptions/${subscriptionToDelete.value.id}`, {
        preserveScroll: true,
        onStart: () => (isDeleting.value = true),
        onSuccess: () => (subscriptionToDelete.value = null),
        onFinish: () => (isDeleting.value = false),
    });
}

function paySubscription(sub: SubscriptionItem) {
    openModalWithDefaults({
        type: 'expense',
        scope: sub.split_mode === 'single' ? 'personal' : 'shared',
        amount: sub.amount,
        title: `Bayar ${sub.name}`,
        notes: `Pembayaran langganan ${sub.billing_cycle === 'monthly' ? 'bulanan' : 'tahunan'}`,
        wallet_id: sub.wallet?.id,
        split_type: sub.split_mode === '50_50' ? 'split_equal' : 'joint_fund',
        source_type: 'subscription',
        source_id: sub.id,
    });
}
</script>

<template>
    <Head title="Langganan & Tagihan Rutin - Couple Finance" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="min-w-0">
                <h1
                    class="text-base font-bold text-zinc-900 dark:text-zinc-100"
                >
                    Langganan & Tagihan Bersama
                </h1>
                <p class="text-xs text-zinc-500">
                    Kelola Netflix, Spotify, Internet, dan tagihan rutin
                </p>
            </div>

            <button
                type="button"
                @click="openCreateModal"
                class="flex min-h-11 items-center gap-1.5 rounded-full bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-indigo-500"
            >
                <Plus class="h-4 w-4" /> Tambah Langganan
            </button>
        </div>

        <!-- Monthly Summary Banner -->
        <div
            class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-950 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800"
        >
            <span
                class="text-xs font-medium tracking-wider text-indigo-300 uppercase"
                >Estimasi Beban Langganan Bulanan</span
            >
            <div
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
            >
                Rp {{ Number(total_monthly_cost).toLocaleString('id-ID') }}
                <span class="text-xs font-normal text-zinc-400">/ bulan</span>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="space-y-3">
            <div
                v-for="sub in subscriptions"
                :key="sub.id"
                class="flex flex-col gap-3 rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-end"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                        :style="{ backgroundColor: sub.color || '#6366F1' }"
                    >
                        <Repeat class="h-5 w-5" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            {{ sub.name }}
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Jatuh tempo:
                            {{
                                new Date(
                                    sub.next_billing_date,
                                ).toLocaleDateString('id-ID', {
                                    day: 'numeric',
                                    month: 'short',
                                })
                            }}
                            •
                            {{
                                sub.billing_cycle === 'monthly'
                                    ? 'Bulanan'
                                    : 'Tahunan'
                            }}
                        </p>
                        <p
                            v-if="sub.last_paid_at"
                            class="mt-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Terakhir dibayar
                            {{
                                new Date(sub.last_paid_at).toLocaleDateString(
                                    'id-ID',
                                    { day: 'numeric', month: 'short' },
                                )
                            }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <span
                            class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Rp {{ Number(sub.amount).toLocaleString('id-ID') }}
                        </span>
                        <div class="text-[10px] text-zinc-400">
                            {{
                                sub.split_mode === '50_50'
                                    ? 'Bagi 50:50'
                                    : 'Dibayar 1 Pihak'
                            }}
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="paySubscription(sub)"
                            class="flex min-h-11 items-center gap-1.5 rounded-xl bg-indigo-600 px-3 text-xs font-bold text-white transition-colors hover:bg-indigo-500"
                            title="Bayar langganan dan catat transaksi"
                        >
                            <CreditCard class="h-4 w-4" /> Bayar
                        </button>
                        <button
                            type="button"
                            @click="openEditModal(sub)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            title="Edit Langganan"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="deleteSubscription(sub)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
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
            @click.self="closeActiveModal"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                @keydown="handleDialogKeydown"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-subscription-title"
                tabindex="-1"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="create-subscription-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Tambah Langganan Baru
                    </h2>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup form langganan"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nama Layanan</label
                        >
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Netflix Premium, Spotify Family, WiFi Indihome"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label
                            for="subscription-wallet"
                            class="block text-xs font-medium text-zinc-500"
                        >
                            Dompet Pembayaran
                        </label>
                        <select
                            id="subscription-wallet"
                            v-model="createForm.wallet_id"
                            class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            <option :value="null">Pilih saat membayar</option>
                            <option
                                v-for="wallet in wallets"
                                :key="wallet.id"
                                :value="wallet.id"
                            >
                                {{ wallet.name }} · Rp
                                {{
                                    Number(wallet.balance).toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nominal Biaya (Rp)</label
                        >
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
                            <label
                                class="block text-xs font-medium text-zinc-500"
                                >Siklus Tagihan</label
                            >
                            <select
                                v-model="createForm.billing_cycle"
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            >
                                <option value="monthly">Bulanan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-medium text-zinc-500"
                                >Jatuh Tempo Berikutnya</label
                            >
                            <input
                                v-model="createForm.next_billing_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Skema Pembagian</label
                        >
                        <select
                            v-model="createForm.split_mode"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="50_50">Bagi Rata (50 : 50)</option>
                            <option value="single">
                                Dibayar Sendiri Sepenuhnya
                            </option>
                            <option value="alternate">
                                Bergantian Tiap Bulan
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Warna Aksen</label
                        >
                        <div class="mt-2 flex gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="createForm.color = c"
                                class="flex h-11 w-11 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                                :aria-label="`Pilih warna ${c}`"
                                :aria-pressed="createForm.color === c"
                            >
                                <Check
                                    v-if="createForm.color === c"
                                    class="h-3.5 w-3.5 text-white"
                                />
                            </button>
                        </div>
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
                                : 'Simpan Langganan'
                        }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingSub"
            @click.self="closeActiveModal"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                @keydown="handleDialogKeydown"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-subscription-title"
                tabindex="-1"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="edit-subscription-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Edit Langganan
                    </h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup form edit langganan"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nama Layanan</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label
                            for="edit-subscription-wallet"
                            class="block text-xs font-medium text-zinc-500"
                        >
                            Dompet Pembayaran
                        </label>
                        <select
                            id="edit-subscription-wallet"
                            v-model="editForm.wallet_id"
                            class="mt-1 min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            <option :value="null">Pilih saat membayar</option>
                            <option
                                v-for="wallet in wallets"
                                :key="wallet.id"
                                :value="wallet.id"
                            >
                                {{ wallet.name }} · Rp
                                {{
                                    Number(wallet.balance).toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nominal Biaya (Rp)</label
                        >
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
                            <label
                                class="block text-xs font-medium text-zinc-500"
                                >Siklus Tagihan</label
                            >
                            <select
                                v-model="editForm.billing_cycle"
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            >
                                <option value="monthly">Bulanan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-medium text-zinc-500"
                                >Jatuh Tempo Berikutnya</label
                            >
                            <input
                                v-model="editForm.next_billing_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Skema Pembagian</label
                        >
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
                        <label class="block text-xs font-medium text-zinc-500"
                            >Warna Aksen</label
                        >
                        <div class="mt-2 flex gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="editForm.color = c"
                                class="flex h-11 w-11 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                                :aria-label="`Pilih warna ${c}`"
                                :aria-pressed="editForm.color === c"
                            >
                                <Check
                                    v-if="editForm.color === c"
                                    class="h-3.5 w-3.5 text-white"
                                />
                            </button>
                        </div>
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
            :open="subscriptionToDelete !== null"
            title="Hapus langganan?"
            :description="`Langganan ${subscriptionToDelete?.name || ''} akan dihapus dari pengingat. Transaksi pembayaran lama tetap tersimpan.`"
            :processing="isDeleting"
            @update:open="subscriptionToDelete = null"
            @confirm="confirmDeleteSubscription"
        />
    </div>
</template>
