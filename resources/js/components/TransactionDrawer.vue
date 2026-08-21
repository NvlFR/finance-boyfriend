<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Check, ArrowRightLeft, TrendingDown, TrendingUp, Users, User as UserIcon } from '@lucide/vue';
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
    return props.categories.filter((cat) => cat.type === (form.type === 'income' ? 'income' : 'expense'));
});

function addQuickAmount(val: number) {
    const current = Number(form.amount) || 0;
    form.amount = current + val;
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
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm transition-opacity sm:items-center sm:p-4"
    >
        <div
            class="w-full max-w-lg rounded-t-3xl border border-zinc-200 bg-white p-5 shadow-2xl transition-all sm:rounded-3xl dark:border-zinc-800 dark:bg-zinc-900 max-h-[90vh] overflow-y-auto"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Catat Transaksi</h2>
                <button
                    type="button"
                    @click="$emit('update:open', false)"
                    class="rounded-full p-1.5 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="mt-4 space-y-4">
                <!-- Type Segmented Tabs -->
                <div class="grid grid-cols-3 gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800/60">
                    <button
                        type="button"
                        @click="form.type = 'expense'"
                        class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-medium transition-all"
                        :class="form.type === 'expense' ? 'bg-rose-500 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                    >
                        <TrendingDown class="h-3.5 w-3.5" /> Pengeluaran
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'income'"
                        class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-medium transition-all"
                        :class="form.type === 'income' ? 'bg-emerald-500 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                    >
                        <TrendingUp class="h-3.5 w-3.5" /> Pemasukan
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'transfer'"
                        class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-medium transition-all"
                        :class="form.type === 'transfer' ? 'bg-indigo-500 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                    >
                        <ArrowRightLeft class="h-3.5 w-3.5" /> Transfer
                    </button>
                </div>

                <!-- Nominal Input -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Nominal (Rp)</label>
                    <div class="relative mt-1">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-lg font-bold text-zinc-400">Rp</span>
                        <input
                            v-model="form.amount"
                            type="number"
                            placeholder="0"
                            autofocus
                            required
                            class="w-full rounded-2xl border border-zinc-300 bg-transparent py-3 pl-12 pr-4 text-2xl font-bold tracking-tight text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-zinc-700 dark:text-zinc-100"
                        />
                    </div>
                    <!-- Quick Amount Chips -->
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="amt in quickAmounts"
                            :key="amt"
                            type="button"
                            @click="addQuickAmount(amt)"
                            class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-medium text-zinc-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-zinc-800 dark:bg-zinc-800/40 dark:text-zinc-400"
                        >
                            +{{ (amt / 1000).toLocaleString() }}k
                        </button>
                    </div>
                </div>

                <!-- Scope (Shared vs Personal) -->
                <div v-if="form.type === 'expense' && partner" class="space-y-1.5">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Peruntukan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="form.scope = 'shared'"
                            class="flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-medium transition-all"
                            :class="form.scope === 'shared' ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:border-rose-500/50 dark:text-rose-400' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                        >
                            <Users class="h-4 w-4" /> Kencan / Bersama
                        </button>
                        <button
                            type="button"
                            @click="form.scope = 'personal'"
                            class="flex items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-medium transition-all"
                            :class="form.scope === 'personal' ? 'border-indigo-500 bg-indigo-500/10 text-indigo-600 dark:border-indigo-500/50 dark:text-indigo-400' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                        >
                            <UserIcon class="h-4 w-4" /> Pribadi Sendiri
                        </button>
                    </div>
                </div>

                <!-- Split Options (If Shared Expense) -->
                <div v-if="form.type === 'expense' && form.scope === 'shared' && partner" class="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-700 dark:text-rose-400">Split Bill / Talangan</span>
                        <span class="text-[10px] text-zinc-500">Dibayar oleh: {{ user.nickname || user.name.split(' ')[0] }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 text-center">
                        <button
                            type="button"
                            @click="form.split_type = 'split_equal'"
                            class="rounded-lg border p-1.5 text-xs font-medium"
                            :class="form.split_type === 'split_equal' ? 'border-rose-500 bg-rose-500 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        >
                            50 : 50
                        </button>
                        <button
                            type="button"
                            @click="form.split_type = 'full_two'"
                            class="rounded-lg border p-1.5 text-xs font-medium"
                            :class="form.split_type === 'full_two' ? 'border-rose-500 bg-rose-500 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        >
                            Talangin Pacar
                        </button>
                        <button
                            type="button"
                            @click="form.split_type = 'full_one'"
                            class="rounded-lg border p-1.5 text-xs font-medium"
                            :class="form.split_type === 'full_one' ? 'border-rose-500 bg-rose-500 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                        >
                            Bayar Sendiri
                        </button>
                    </div>
                </div>

                <!-- Source Wallet -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">
                        {{ form.type === 'transfer' ? 'Dari Dompet' : 'Dompet' }}
                    </label>
                    <select
                        v-model="form.wallet_id"
                        required
                        class="mt-1 w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option v-for="w in wallets" :key="w.id" :value="w.id">
                            {{ w.name }} ({{ w.type === 'joint' ? 'Kas Bersama' : (w.user?.nickname || w.user?.name || 'Pribadi') }}) - Rp {{ Number(w.balance).toLocaleString() }}
                        </option>
                    </select>
                </div>

                <!-- Destination Wallet (If Transfer) -->
                <div v-if="form.type === 'transfer'">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Ke Dompet Tujuan</label>
                    <select
                        v-model="form.to_wallet_id"
                        required
                        class="mt-1 w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option :value="null">Pilih Dompet Tujuan...</option>
                        <option
                            v-for="w in wallets.filter(w => w.id !== Number(form.wallet_id))"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.name }} ({{ w.type === 'joint' ? 'Kas Bersama' : (w.user?.nickname || w.user?.name || 'Pribadi') }})
                        </option>
                    </select>
                </div>

                <!-- Category (If Not Transfer) -->
                <div v-if="form.type !== 'transfer'">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Kategori</label>
                    <select
                        v-model="form.category_id"
                        class="mt-1 w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option :value="null">Tanpa Kategori</option>
                        <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <!-- Title & Notes -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Judul / Catatan (Opsional)</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Contoh: Makan Ramen di Mall, Tiket Bioskop"
                        class="mt-1 w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:text-zinc-100"
                    />
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing || !form.amount"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition-all hover:opacity-90 active:scale-[0.99] disabled:opacity-50"
                >
                    <Check class="h-4 w-4" /> Simpan Transaksi
                </button>
            </form>
        </div>
    </div>
</template>
