<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    X,
    Check,
    ArrowRightLeft,
    TrendingDown,
    TrendingUp,
    Users,
    User as UserIcon,
    WalletCards,
    Landmark,
    Tag,
    Sparkles,
    CreditCard,
    Coins,
} from '@lucide/vue';
import type { Wallet, Category } from '@/types/finance';
import type { User } from '@/types/auth';

const props = defineProps<{
    open: boolean;
    wallets: Wallet[];
    categories: Category[];
    user: User;
    partner?: User | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'created'): void;
}>();

const quickAmounts = [10000, 25000, 50000, 100000, 250000, 500000];

const form = useForm({
    type: 'expense' as 'expense' | 'income' | 'transfer',
    scope: 'shared' as 'personal' | 'shared',
    wallet_id: props.wallets[0]?.id || ('' as unknown as number),
    to_wallet_id: null as number | null,
    category_id: props.categories[0]?.id || null,
    amount: '' as string | number,
    transaction_date: new Date().toISOString().slice(0, 16),
    title: '',
    notes: '',
    split_type: 'split_equal',
    user_one_amount: 0,
    user_two_amount: 0,
    paid_by_user_id: props.user.id,
});

watch(
    () => props.open,
    (val) => {
        if (val) {
            form.transaction_date = new Date().toISOString().slice(0, 16);
            if (!form.wallet_id && props.wallets.length > 0) {
                form.wallet_id = props.wallets[0].id;
            }
        }
    }
);

// Auto-calculate 50:50 splits when amount changes
watch([() => form.amount, () => form.split_type], ([amount, splitType]) => {
    const numAmount = Number(amount) || 0;
    if (splitType === 'split_equal') {
        form.user_one_amount = Math.round(numAmount / 2);
        form.user_two_amount = numAmount - form.user_one_amount;
    } else if (splitType === 'full_one') {
        form.user_one_amount = numAmount;
        form.user_two_amount = 0;
    } else if (splitType === 'full_two') {
        form.user_one_amount = 0;
        form.user_two_amount = numAmount;
    }
});

const filteredCategories = computed(() => {
    return props.categories.filter((cat) => {
        if (form.type === 'income') return cat.type === 'income' || cat.type === 'both';
        return cat.type === 'expense' || cat.type === 'both';
    });
});

const selectedWallet = computed(() => {
    return props.wallets.find((w) => w.id === Number(form.wallet_id));
});

const selectedToWallet = computed(() => {
    return props.wallets.find((w) => w.id === Number(form.to_wallet_id));
});

const selectedCategory = computed(() => {
    return props.categories.find((c) => c.id === Number(form.category_id));
});

function addQuickAmount(val: number) {
    const current = Number(form.amount) || 0;
    form.amount = current + val;
}

function selectWallet(id: number) {
    form.wallet_id = id;
}

function selectToWallet(id: number) {
    form.to_wallet_id = id;
}

function selectCategory(id: number | null) {
    form.category_id = id;
}

function submit() {
    form.post('/transactions', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('update:open', false);
            emit('created');
        },
    });
}
</script>

<template>
    <div
        v-if="open"
        @click.self="$emit('update:open', false)"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm transition-opacity sm:items-center sm:p-4 cursor-pointer"
    >
        <div
            @click.stop
            class="w-full max-w-lg rounded-t-3xl border border-zinc-200 bg-white p-5 shadow-2xl transition-all sm:rounded-3xl dark:border-zinc-800 dark:bg-zinc-900 max-h-[92vh] overflow-y-auto space-y-4 cursor-default"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Catat Transaksi</h2>
                        <p class="text-[11px] text-zinc-500">Pemasukan, pengeluaran, atau transfer kencan</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="$emit('update:open', false)"
                    class="rounded-full p-1.5 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Type Segmented Tabs -->
                <div class="grid grid-cols-3 gap-1 rounded-2xl bg-zinc-100 p-1 dark:bg-zinc-800/60">
                    <button
                        type="button"
                        @click="form.type = 'expense'"
                        class="flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold transition-all"
                        :class="form.type === 'expense' ? 'bg-rose-500 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                    >
                        <TrendingDown class="h-3.5 w-3.5" /> Pengeluaran
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'income'"
                        class="flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold transition-all"
                        :class="form.type === 'income' ? 'bg-emerald-500 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                    >
                        <TrendingUp class="h-3.5 w-3.5" /> Pemasukan
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'transfer'"
                        class="flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold transition-all"
                        :class="form.type === 'transfer' ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                    >
                        <ArrowRightLeft class="h-3.5 w-3.5" /> Transfer
                    </button>
                </div>

                <!-- Nominal Input -->
                <div class="rounded-3xl border border-zinc-200/80 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Nominal Transaksi
                    </label>
                    <div class="relative mt-1">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 text-2xl font-black text-zinc-400">Rp</span>
                        <input
                            v-model="form.amount"
                            type="number"
                            placeholder="0"
                            autofocus
                            required
                            min="1"
                            class="w-full bg-transparent py-2 pl-12 pr-2 text-3xl font-black tracking-tight text-zinc-900 focus:outline-none dark:text-zinc-100"
                        />
                    </div>
                    <!-- Quick Amount Chips -->
                    <div class="mt-2 flex flex-wrap gap-1.5 border-t border-zinc-200/60 pt-2 dark:border-zinc-700/60">
                        <button
                            v-for="amt in quickAmounts"
                            :key="amt"
                            type="button"
                            @click="addQuickAmount(amt)"
                            class="rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-[11px] font-bold text-zinc-700 hover:border-indigo-400 hover:text-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 transition-all active:scale-95"
                        >
                            +{{ (amt / 1000).toLocaleString('id-ID') }}k
                        </button>
                    </div>
                </div>

                <!-- Scope (Shared vs Personal) -->
                <div v-if="form.type === 'expense' && partner" class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Cakupan Transaksi</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="form.scope = 'shared'"
                            class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                            :class="form.scope === 'shared' ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:border-rose-500/50 dark:text-rose-400 shadow-sm' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                        >
                            <Users class="h-4 w-4" /> Kencan Bersama
                        </button>
                        <button
                            type="button"
                            @click="form.scope = 'personal'"
                            class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                            :class="form.scope === 'personal' ? 'border-indigo-500 bg-indigo-500/10 text-indigo-600 dark:border-indigo-500/50 dark:text-indigo-400 shadow-sm' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                        >
                            <UserIcon class="h-4 w-4" /> Pribadi Sendiri
                        </button>
                    </div>
                </div>

                <!-- Split Options (If Shared Expense) -->
                <div v-if="form.type === 'expense' && form.scope === 'shared' && partner" class="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rose-700 dark:text-rose-400">Pembagian Biaya (Split Bill)</span>
                        <span class="text-[10px] text-zinc-500">Talangan oleh: {{ user.nickname || user.name.split(' ')[0] }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 text-center">
                        <button
                            type="button"
                            @click="form.split_type = 'split_equal'"
                            class="rounded-xl border p-2 text-xs font-bold transition-all"
                            :class="form.split_type === 'split_equal' ? 'border-rose-500 bg-rose-500 text-white shadow-sm' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        >
                            50 : 50
                        </button>
                        <button
                            type="button"
                            @click="form.split_type = 'full_two'"
                            class="rounded-xl border p-2 text-xs font-bold transition-all"
                            :class="form.split_type === 'full_two' ? 'border-rose-500 bg-rose-500 text-white shadow-sm' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        >
                            Talangin Pacar
                        </button>
                        <button
                            type="button"
                            @click="form.split_type = 'full_one'"
                            class="rounded-xl border p-2 text-xs font-bold transition-all"
                            :class="form.split_type === 'full_one' ? 'border-rose-500 bg-rose-500 text-white shadow-sm' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        >
                            Bayar Sendiri
                        </button>
                    </div>
                </div>

                <!-- 💳 1. Visual Wallet / Bank Selector (Interactive Grid Cards) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                            {{ form.type === 'transfer' ? 'Dari Dompet / Rekening Asal' : 'Pilih Dompet / Rekening' }}
                        </label>
                        <span v-if="selectedWallet" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                            Saldo: Rp {{ Number(selectedWallet.balance).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto pr-1">
                        <div
                            v-for="w in wallets"
                            :key="w.id"
                            @click="selectWallet(w.id)"
                            class="relative flex cursor-pointer flex-col justify-between rounded-2xl border p-3 transition-all active:scale-[0.98]"
                            :class="[
                                form.wallet_id === w.id
                                    ? 'border-indigo-600 bg-indigo-50/70 shadow-sm dark:border-indigo-400 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700'
                            ]"
                        >
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-white shadow-xs"
                                        :style="{ backgroundColor: w.color || '#6366F1' }"
                                    >
                                        <Landmark v-if="w.type === 'bank'" class="h-3.5 w-3.5" />
                                        <Coins v-else-if="w.type === 'cash'" class="h-3.5 w-3.5" />
                                        <CreditCard v-else class="h-3.5 w-3.5" />
                                    </div>
                                    <span class="text-xs font-bold text-zinc-900 truncate dark:text-zinc-100">
                                        {{ w.name }}
                                    </span>
                                </div>

                                <div
                                    v-if="form.wallet_id === w.id"
                                    class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-600 text-white"
                                >
                                    <Check class="h-2.5 w-2.5 stroke-[3]" />
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[10px]">
                                <span
                                    class="rounded-md px-1.5 py-0.5 font-medium"
                                    :class="w.type === 'joint' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'"
                                >
                                    {{ w.type === 'joint' ? 'Kas Bersama' : (w.user?.nickname || w.user?.name?.split(' ')[0] || 'Pribadi') }}
                                </span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                    Rp {{ Number(w.balance).toLocaleString('id-ID') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 💳 Destination Wallet (If Transfer) -->
                <div v-if="form.type === 'transfer'" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                            Ke Dompet / Rekening Tujuan
                        </label>
                        <span v-if="selectedToWallet" class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                            Saldo: Rp {{ Number(selectedToWallet.balance).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto pr-1">
                        <div
                            v-for="w in wallets.filter(w => w.id !== Number(form.wallet_id))"
                            :key="w.id"
                            @click="selectToWallet(w.id)"
                            class="relative flex cursor-pointer flex-col justify-between rounded-2xl border p-3 transition-all active:scale-[0.98]"
                            :class="[
                                form.to_wallet_id === w.id
                                    ? 'border-emerald-600 bg-emerald-50/70 shadow-sm dark:border-emerald-400 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700'
                            ]"
                        >
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-white shadow-xs"
                                        :style="{ backgroundColor: w.color || '#10B981' }"
                                    >
                                        <Landmark v-if="w.type === 'bank'" class="h-3.5 w-3.5" />
                                        <Coins v-else-if="w.type === 'cash'" class="h-3.5 w-3.5" />
                                        <CreditCard v-else class="h-3.5 w-3.5" />
                                    </div>
                                    <span class="text-xs font-bold text-zinc-900 truncate dark:text-zinc-100">
                                        {{ w.name }}
                                    </span>
                                </div>

                                <div
                                    v-if="form.to_wallet_id === w.id"
                                    class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-white"
                                >
                                    <Check class="h-2.5 w-2.5 stroke-[3]" />
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[10px]">
                                <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ w.type === 'joint' ? 'Kas Bersama' : (w.user?.nickname || w.user?.name?.split(' ')[0] || 'Pribadi') }}
                                </span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                    Rp {{ Number(w.balance).toLocaleString('id-ID') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🏷️ 2. Visual Category Selector (Interactive Colorful Chips Grid) -->
                <div v-if="form.type !== 'transfer'" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-zinc-800 dark:text-zinc-200">
                            Pilih Kategori
                        </label>
                        <span v-if="selectedCategory" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                            {{ selectedCategory.name }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto p-1">
                        <!-- Tanpa Kategori Option -->
                        <button
                            type="button"
                            @click="selectCategory(null)"
                            class="inline-flex items-center gap-1.5 rounded-2xl border px-3 py-1.5 text-xs font-semibold transition-all active:scale-95"
                            :class="form.category_id === null ? 'border-indigo-600 bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400 shadow-xs' : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'"
                        >
                            <Tag class="h-3.5 w-3.5 text-zinc-400" />
                            <span>Tanpa Kategori</span>
                        </button>

                        <!-- All Available Categories -->
                        <button
                            v-for="cat in filteredCategories"
                            :key="cat.id"
                            type="button"
                            @click="selectCategory(cat.id)"
                            class="inline-flex items-center gap-2 rounded-2xl border px-3 py-1.5 text-xs font-semibold transition-all active:scale-95"
                            :class="[
                                form.category_id === cat.id
                                    ? 'border-indigo-600 bg-indigo-50/80 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/50 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-xs'
                                    : 'border-zinc-200/80 bg-white text-zinc-700 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'
                            ]"
                        >
                            <span
                                class="flex h-4 w-4 items-center justify-center rounded-full text-white text-[9px] shadow-xs"
                                :style="{ backgroundColor: cat.color || '#6366F1' }"
                            >
                                <Check v-if="form.category_id === cat.id" class="h-2.5 w-2.5 stroke-[3]" />
                            </span>
                            <span>{{ cat.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Title & Notes -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Judul Transaksi / Keterangan
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Contoh: Makan Ramen di Mall, Tiket Bioskop, Kopi Sore"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.amount"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3.5 text-xs font-bold text-white shadow-lg shadow-indigo-500/25 transition-all hover:opacity-95 active:scale-[0.99] disabled:opacity-50"
                    >
                        <Check class="h-4 w-4 stroke-[2.5]" />
                        <span>Simpan Transaksi Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
