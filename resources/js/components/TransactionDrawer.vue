<script setup lang="ts">
import { useForm, useHttp, usePage } from '@inertiajs/vue3';
import {
    X,
    Check,
    ArrowRightLeft,
    TrendingDown,
    TrendingUp,
    Users,
    User as UserIcon,
    Landmark,
    Tag,
    Sparkles,
    CreditCard,
    Coins,
    Plus,
} from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import { store as categoryStore } from '@/routes/categories';
import { store as transactionStore } from '@/routes/transactions';
import type { User } from '@/types/auth';
import type {
    Wallet,
    Category,
    CoupleSpace,
    SplitType,
    TransactionDefaults,
} from '@/types/finance';

const page = usePage();

const props = withDefaults(
    defineProps<{
        open?: boolean;
        wallets?: Wallet[];
        categories?: Category[];
        user?: User;
        partner?: User | null;
        defaults?: TransactionDefaults;
    }>(),
    {
        open: false,
        wallets: () => [],
        categories: () => [],
        user: undefined,
        partner: null,
        defaults: () => ({}),
    },
);

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'created'): void;
}>();

const effectiveWallets = computed<Wallet[]>(() =>
    props.wallets && props.wallets.length > 0
        ? props.wallets
        : (page.props as any).wallets || [],
);
const createdCategories = ref<Category[]>([]);
const effectiveCategories = computed(() => {
    const categories =
        props.categories && props.categories.length > 0
            ? props.categories
            : (page.props as any).categories || [];
    const categoryMap = new Map<number, Category>();

    [...categories, ...createdCategories.value].forEach((category: Category) =>
        categoryMap.set(category.id, category),
    );

    return [...categoryMap.values()];
});
const effectiveUser = computed(
    () => props.user || (page.props.auth as any)?.user,
);
const sourceWallets = computed(() =>
    effectiveWallets.value.filter(
        (wallet: Wallet) =>
            wallet.type === 'joint' ||
            wallet.user_id === effectiveUser.value?.id,
    ),
);
const effectiveCoupleSpace = computed(
    () => (page.props as any).coupleSpace as CoupleSpace | null,
);
const partnerFullSplitType = computed<SplitType>(() =>
    effectiveCoupleSpace.value?.user_one_id === effectiveUser.value?.id
        ? 'full_two'
        : 'full_one',
);
const selfFullSplitType = computed<SplitType>(() =>
    effectiveCoupleSpace.value?.user_one_id === effectiveUser.value?.id
        ? 'full_one'
        : 'full_two',
);
const isContextualExpense = computed(() => Boolean(props.defaults.source_type));
const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    () => props.open,
    () => emit('update:open', false),
);
const quickAmounts = [10000, 25000, 50000, 100000, 250000, 500000];

function localDateTimeInputValue(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 16);
}

function createClientReference(): string {
    return (
        globalThis.crypto?.randomUUID?.() ||
        `transaction-${Date.now()}-${Math.random().toString(36).slice(2)}`
    );
}

const form = useForm({
    type: 'expense' as 'expense' | 'income' | 'transfer',
    scope: 'personal' as 'personal' | 'shared',
    wallet_id: sourceWallets.value[0]?.id || ('' as unknown as number),
    to_wallet_id: null as number | null,
    category_id: effectiveCategories.value[0]?.id || null,
    amount: '' as string | number,
    fee_amount: 0 as string | number,
    transaction_date: localDateTimeInputValue(),
    title: '',
    notes: '',
    client_reference: createClientReference(),
    source_type: null as TransactionDefaults['source_type'] | null,
    source_id: null as number | null,
    split: {
        split_type: 'split_equal' as SplitType,
        user_one_amount: 0,
        user_two_amount: 0,
        paid_by_user_id: effectiveUser.value?.id || 0,
    },
});

watch(
    () => props.open,
    (val) => {
        if (val) {
            form.client_reference = createClientReference();
            form.transaction_date = localDateTimeInputValue();
            form.type = props.defaults.type || 'expense';
            form.scope = props.defaults.scope || 'personal';
            form.title = props.defaults.title || '';
            form.notes = props.defaults.notes || '';
            form.amount = props.defaults.amount || '';
            form.fee_amount = 0;
            form.source_type = props.defaults.source_type || null;
            form.source_id = props.defaults.source_id || null;
            form.split.paid_by_user_id = effectiveUser.value?.id || 0;
            form.split.split_type = props.defaults.split_type || 'split_equal';

            if (
                props.defaults.wallet_id &&
                sourceWallets.value.some(
                    (wallet) => wallet.id === props.defaults.wallet_id,
                )
            ) {
                form.wallet_id = props.defaults.wallet_id;
            } else if (
                !sourceWallets.value.some(
                    (wallet) => wallet.id === Number(form.wallet_id),
                ) &&
                sourceWallets.value.length > 0
            ) {
                form.wallet_id = sourceWallets.value[0].id;
            }

            form.category_id =
                props.defaults.category_id ??
                filteredCategories.value[0]?.id ??
                null;
        }
    },
);

// Auto-calculate 50:50 splits when amount changes
watch(
    [() => form.amount, () => form.split.split_type],
    ([amount, splitType]) => {
        const numAmount = Number(amount) || 0;

        if (splitType === 'split_equal') {
            form.split.user_one_amount = Math.round(numAmount / 2);
            form.split.user_two_amount = numAmount - form.split.user_one_amount;
        } else if (splitType === 'full_one') {
            form.split.user_one_amount = numAmount;
            form.split.user_two_amount = 0;
        } else if (splitType === 'full_two') {
            form.split.user_one_amount = 0;
            form.split.user_two_amount = numAmount;
        }
    },
);

const filteredCategories = computed(() => {
    return effectiveCategories.value.filter((cat: Category) => {
        if (form.type === 'income') {
            return cat.type === 'income' || cat.type === 'both';
        }

        return cat.type === 'expense' || cat.type === 'both';
    });
});

watch(
    () => form.type,
    (type) => {
        if (type === 'transfer') {
            form.category_id = null;
            form.scope = 'personal';

            return;
        }

        if (type === 'income') {
            form.scope = 'personal';
        }

        if (
            !filteredCategories.value.some(
                (category) => category.id === Number(form.category_id),
            )
        ) {
            form.category_id = filteredCategories.value[0]?.id ?? null;
        }
    },
);

const isCategoryModalOpen = ref(false);
const {
    dialogRef: categoryDialogRef,
    handleDialogKeydown: handleCategoryDialogKeydown,
} = useAccessibleDialog(
    () => isCategoryModalOpen.value,
    () => (isCategoryModalOpen.value = false),
);
const categoryColors = [
    '#6366F1',
    '#EC4899',
    '#F43F5E',
    '#10B981',
    '#F59E0B',
    '#3B82F6',
    '#8B5CF6',
    '#14B8A6',
];
const categoryForm = useHttp<
    {
        name: string;
        type: 'income' | 'expense';
        icon: string;
        color: string;
    },
    { category: Category }
>({
    name: '',
    type: 'expense',
    icon: 'tag',
    color: '#6366F1',
});

function openCategoryModal() {
    categoryForm.type = form.type === 'income' ? 'income' : 'expense';
    categoryForm.name = '';
    categoryForm.clearErrors();
    isCategoryModalOpen.value = true;
}

function submitCategory() {
    categoryForm.post(categoryStore.url(), {
        onSuccess: (response) => {
            createdCategories.value.push(response.category);
            form.category_id = response.category.id;
            isCategoryModalOpen.value = false;
            categoryForm.reset('name');
        },
    });
}

const selectedWallet = computed(() => {
    return sourceWallets.value.find(
        (w: Wallet) => w.id === Number(form.wallet_id),
    );
});

const selectedToWallet = computed(() => {
    return effectiveWallets.value.find(
        (w: Wallet) => w.id === Number(form.to_wallet_id),
    );
});

const transferSourceDebit = computed(
    () => Number(form.amount || 0) + Number(form.fee_amount || 0),
);

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
    form.post(transactionStore.url(), {
        preserveScroll: true,
        replace: true,
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
        class="fixed inset-0 z-50 flex cursor-pointer items-end justify-center bg-black/60 backdrop-blur-sm transition-opacity sm:items-center sm:p-4"
    >
        <div
            ref="dialogRef"
            @click.stop
            @keydown="handleDialogKeydown"
            role="dialog"
            aria-modal="true"
            aria-labelledby="transaction-dialog-title"
            tabindex="-1"
            class="max-h-[92dvh] w-full max-w-lg cursor-default space-y-4 overflow-y-auto rounded-t-3xl border border-zinc-200 bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl transition-all sm:rounded-3xl dark:border-zinc-800 dark:bg-zinc-900"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                    >
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <div>
                        <h2
                            id="transaction-dialog-title"
                            class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Catat Transaksi
                        </h2>
                        <p class="text-[11px] text-zinc-500">
                            Catat arus uang tanpa langkah yang tidak perlu
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="$emit('update:open', false)"
                    class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    aria-label="Tutup form transaksi"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Type Segmented Tabs -->
                <div
                    v-if="!isContextualExpense"
                    class="grid grid-cols-3 gap-1 rounded-2xl bg-zinc-100 p-1 dark:bg-zinc-800/60"
                >
                    <button
                        type="button"
                        @click="form.type = 'expense'"
                        class="flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold transition-all"
                        :class="
                            form.type === 'expense'
                                ? 'bg-rose-500 text-white shadow-sm'
                                : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'
                        "
                    >
                        <TrendingDown class="h-3.5 w-3.5" /> Pengeluaran
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'income'"
                        class="flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold transition-all"
                        :class="
                            form.type === 'income'
                                ? 'bg-emerald-500 text-white shadow-sm'
                                : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'
                        "
                    >
                        <TrendingUp class="h-3.5 w-3.5" /> Pemasukan
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'transfer'"
                        class="flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-bold transition-all"
                        :class="
                            form.type === 'transfer'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'
                        "
                    >
                        <ArrowRightLeft class="h-3.5 w-3.5" /> Transfer
                    </button>
                </div>
                <div
                    v-else
                    class="flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
                >
                    <TrendingDown class="h-4 w-4 shrink-0" />
                    Pembayaran ini otomatis dicatat sebagai pengeluaran.
                </div>

                <!-- Nominal Input -->
                <div
                    class="rounded-3xl border border-zinc-200/80 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30"
                >
                    <label
                        for="transaction-amount"
                        class="block text-[11px] font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400"
                    >
                        Nominal Transaksi
                    </label>
                    <div class="relative mt-1">
                        <span
                            class="absolute top-1/2 left-0 -translate-y-1/2 text-2xl font-black text-zinc-400"
                            >Rp</span
                        >
                        <CurrencyInput
                            v-model="form.amount"
                            id="transaction-amount"
                            placeholder="0"
                            required
                            min="1"
                            class="w-full bg-transparent py-2 pr-2 pl-12 text-3xl font-black tracking-tight text-zinc-900 focus:outline-none dark:text-zinc-100"
                        />
                    </div>
                    <!-- Quick Amount Chips -->
                    <div
                        class="mt-2 flex flex-wrap gap-1.5 border-t border-zinc-200/60 pt-2 dark:border-zinc-700/60"
                    >
                        <button
                            v-for="amt in quickAmounts"
                            :key="amt"
                            type="button"
                            @click="addQuickAmount(amt)"
                            class="rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-[11px] font-bold text-zinc-700 transition-all hover:border-indigo-400 hover:text-indigo-600 active:scale-95 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                        >
                            +{{ (amt / 1000).toLocaleString('id-ID') }}k
                        </button>
                    </div>
                </div>

                <!-- Scope (Shared vs Personal) -->
                <div
                    v-if="partner && form.type === 'expense'"
                    class="space-y-1.5"
                >
                    <label
                        class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                        >Cakupan Transaksi</label
                    >
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="form.scope = 'personal'"
                            class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                            :class="
                                form.scope === 'personal'
                                    ? 'border-indigo-500 bg-indigo-500/10 text-indigo-600 shadow-sm dark:border-indigo-500/50 dark:text-indigo-400'
                                    : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'
                            "
                        >
                            <UserIcon class="h-4 w-4" /> Pribadi
                        </button>
                        <button
                            type="button"
                            @click="form.scope = 'shared'"
                            class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                            :class="
                                form.scope === 'shared'
                                    ? 'border-rose-500 bg-rose-500/10 text-rose-600 shadow-sm dark:border-rose-500/50 dark:text-rose-400'
                                    : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'
                            "
                        >
                            <Users class="h-4 w-4" /> Bersama
                        </button>
                    </div>
                </div>

                <!-- Split Options (If Shared Expense) -->
                <div
                    v-if="
                        form.type === 'expense' &&
                        form.scope === 'shared' &&
                        partner
                    "
                    class="space-y-2 rounded-2xl border border-rose-500/20 bg-rose-500/5 p-3"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold text-rose-700 dark:text-rose-400"
                            >Pembagian Biaya (Split Bill)</span
                        >
                        <span class="text-[10px] text-zinc-500"
                            >Talangan oleh:
                            {{
                                effectiveUser?.nickname ||
                                effectiveUser?.name?.split(' ')[0]
                            }}</span
                        >
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 text-center">
                        <button
                            type="button"
                            @click="form.split.split_type = 'split_equal'"
                            class="rounded-xl border p-2 text-xs font-bold transition-all"
                            :class="
                                form.split.split_type === 'split_equal'
                                    ? 'border-rose-500 bg-rose-500 text-white shadow-sm'
                                    : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                            "
                        >
                            50 : 50
                        </button>
                        <button
                            type="button"
                            @click="
                                form.split.split_type = partnerFullSplitType
                            "
                            class="rounded-xl border p-2 text-xs font-bold transition-all"
                            :class="
                                form.split.split_type === partnerFullSplitType
                                    ? 'border-rose-500 bg-rose-500 text-white shadow-sm'
                                    : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                            "
                        >
                            Talangin Pacar
                        </button>
                        <button
                            type="button"
                            @click="form.split.split_type = selfFullSplitType"
                            class="rounded-xl border p-2 text-xs font-bold transition-all"
                            :class="
                                form.split.split_type === selfFullSplitType
                                    ? 'border-rose-500 bg-rose-500 text-white shadow-sm'
                                    : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                            "
                        >
                            Bayar Sendiri
                        </button>
                    </div>
                </div>

                <!-- 💳 1. Visual Wallet / Bank Selector (Interactive Grid Cards) -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            class="block text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                            {{
                                form.type === 'transfer'
                                    ? 'Dari Dompet / Rekening Asal'
                                    : 'Pilih Dompet / Rekening'
                            }}
                        </label>
                        <span
                            v-if="selectedWallet"
                            class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400"
                        >
                            Saldo: Rp
                            {{
                                Number(selectedWallet.balance).toLocaleString(
                                    'id-ID',
                                )
                            }}
                        </span>
                    </div>

                    <div
                        class="grid max-h-44 grid-cols-2 gap-2 overflow-y-auto pr-1"
                    >
                        <button
                            v-for="w in sourceWallets"
                            :key="w.id"
                            type="button"
                            @click="selectWallet(w.id)"
                            :aria-pressed="form.wallet_id === w.id"
                            class="relative flex cursor-pointer flex-col justify-between rounded-2xl border p-3 text-left transition-all active:scale-[0.98]"
                            :class="[
                                form.wallet_id === w.id
                                    ? 'border-indigo-600 bg-indigo-50/70 shadow-sm ring-2 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-950/40'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700',
                            ]"
                        >
                            <div
                                class="flex items-center justify-between gap-1.5"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-white shadow-xs"
                                        :style="{
                                            backgroundColor:
                                                w.color || '#6366F1',
                                        }"
                                    >
                                        <Landmark
                                            v-if="w.wallet_type === 'bank'"
                                            class="h-3.5 w-3.5"
                                        />
                                        <Coins
                                            v-else-if="w.wallet_type === 'cash'"
                                            class="h-3.5 w-3.5"
                                        />
                                        <CreditCard
                                            v-else
                                            class="h-3.5 w-3.5"
                                        />
                                    </div>
                                    <span
                                        class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100"
                                    >
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

                            <div
                                class="mt-2 flex items-center justify-between text-[10px]"
                            >
                                <span
                                    class="rounded-md px-1.5 py-0.5 font-medium"
                                    :class="
                                        w.type === 'joint'
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                            : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'
                                    "
                                >
                                    {{
                                        w.type === 'joint'
                                            ? 'Kas Bersama'
                                            : w.user?.nickname ||
                                              w.user?.name?.split(' ')[0] ||
                                              'Pribadi'
                                    }}
                                </span>
                                <span
                                    class="font-bold text-zinc-800 dark:text-zinc-200"
                                >
                                    Rp
                                    {{
                                        Number(w.balance).toLocaleString(
                                            'id-ID',
                                        )
                                    }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 💳 Destination Wallet (If Transfer) -->
                <div v-if="form.type === 'transfer'" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            class="block text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                            Ke Dompet / Rekening Tujuan
                        </label>
                        <span
                            v-if="selectedToWallet"
                            class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Saldo: Rp
                            {{
                                Number(selectedToWallet.balance).toLocaleString(
                                    'id-ID',
                                )
                            }}
                        </span>
                    </div>

                    <div
                        class="grid max-h-44 grid-cols-2 gap-2 overflow-y-auto pr-1"
                    >
                        <button
                            v-for="w in effectiveWallets.filter(
                                (w: Wallet) => w.id !== Number(form.wallet_id),
                            )"
                            :key="w.id"
                            type="button"
                            @click="selectToWallet(w.id)"
                            :aria-pressed="form.to_wallet_id === w.id"
                            class="relative flex cursor-pointer flex-col justify-between rounded-2xl border p-3 text-left transition-all active:scale-[0.98]"
                            :class="[
                                form.to_wallet_id === w.id
                                    ? 'border-emerald-600 bg-emerald-50/70 shadow-sm ring-2 ring-emerald-500/20 dark:border-emerald-400 dark:bg-emerald-950/40'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700',
                            ]"
                        >
                            <div
                                class="flex items-center justify-between gap-1.5"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-white shadow-xs"
                                        :style="{
                                            backgroundColor:
                                                w.color || '#10B981',
                                        }"
                                    >
                                        <Landmark
                                            v-if="w.wallet_type === 'bank'"
                                            class="h-3.5 w-3.5"
                                        />
                                        <Coins
                                            v-else-if="w.wallet_type === 'cash'"
                                            class="h-3.5 w-3.5"
                                        />
                                        <CreditCard
                                            v-else
                                            class="h-3.5 w-3.5"
                                        />
                                    </div>
                                    <span
                                        class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100"
                                    >
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

                            <div
                                class="mt-2 flex items-center justify-between text-[10px]"
                            >
                                <span
                                    class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                                >
                                    {{
                                        w.type === 'joint'
                                            ? 'Kas Bersama'
                                            : w.user?.nickname ||
                                              w.user?.name?.split(' ')[0] ||
                                              'Pribadi'
                                    }}
                                </span>
                                <span
                                    class="font-bold text-zinc-800 dark:text-zinc-200"
                                >
                                    Rp
                                    {{
                                        Number(w.balance).toLocaleString(
                                            'id-ID',
                                        )
                                    }}
                                </span>
                            </div>
                        </button>
                    </div>

                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-900/60 dark:bg-amber-950/30"
                    >
                        <label
                            for="transaction-fee"
                            class="text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                            Biaya Admin
                            <span class="font-normal text-zinc-500"
                                >(opsional)</span
                            >
                        </label>
                        <div class="relative mt-1.5">
                            <span
                                class="absolute top-1/2 left-3 -translate-y-1/2 text-xs font-bold text-zinc-500"
                                >Rp</span
                            >
                            <CurrencyInput
                                id="transaction-fee"
                                v-model="form.fee_amount"
                                min="0"
                                class="min-h-11 w-full rounded-xl border border-amber-200 bg-white py-2 pr-3 pl-9 text-sm font-bold text-zinc-900 focus:border-amber-500 focus:outline-none dark:border-amber-900 dark:bg-zinc-900 dark:text-zinc-100"
                            />
                        </div>
                        <p
                            class="mt-2 text-[11px] leading-4 text-zinc-600 dark:text-zinc-400"
                        >
                            Tujuan menerima Rp
                            {{
                                Number(form.amount || 0).toLocaleString(
                                    'id-ID',
                                )
                            }}. Saldo
                            {{ selectedWallet?.name || 'asal' }} berkurang Rp
                            {{ transferSourceDebit.toLocaleString('id-ID') }}.
                        </p>
                        <p
                            v-if="selectedToWallet?.wallet_type === 'cash'"
                            class="mt-1 text-[11px] font-semibold text-amber-700 dark:text-amber-400"
                        >
                            Tarik tunai dicatat sebagai transfer dari rekening
                            ke dompet Uang Tunai.
                        </p>
                    </div>
                </div>

                <!-- 🏷️ 2. Visual Category Selector (Interactive Colorful Chips Grid) -->
                <div v-if="form.type !== 'transfer'" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            class="block text-xs font-bold text-zinc-800 dark:text-zinc-200"
                        >
                            Pilih Kategori
                        </label>
                        <button
                            type="button"
                            @click="openCategoryModal"
                            class="inline-flex min-h-11 items-center gap-1 rounded-xl px-2 text-[11px] font-bold text-indigo-600 transition-colors hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40"
                        >
                            <Plus class="h-3.5 w-3.5" /> Kategori Baru
                        </button>
                    </div>

                    <div
                        class="flex max-h-40 flex-wrap gap-2 overflow-y-auto p-1"
                    >
                        <!-- Tanpa Kategori Option -->
                        <button
                            type="button"
                            @click="selectCategory(null)"
                            class="inline-flex items-center gap-1.5 rounded-2xl border px-3 py-1.5 text-xs font-semibold transition-all active:scale-95"
                            :class="
                                form.category_id === null
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-600 shadow-xs dark:bg-indigo-950/40 dark:text-indigo-400'
                                    : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'
                            "
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
                                    ? 'border-indigo-600 bg-indigo-50/80 text-indigo-700 shadow-xs ring-2 ring-indigo-500/20 dark:border-indigo-400 dark:bg-indigo-950/50 dark:text-indigo-300'
                                    : 'border-zinc-200/80 bg-white text-zinc-700 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
                            ]"
                        >
                            <span
                                class="flex h-4 w-4 items-center justify-center rounded-full text-[9px] text-white shadow-xs"
                                :style="{
                                    backgroundColor: cat.color || '#6366F1',
                                }"
                            >
                                <Check
                                    v-if="form.category_id === cat.id"
                                    class="h-2.5 w-2.5 stroke-[3]"
                                />
                            </span>
                            <span>{{ cat.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Title & Notes -->
                <div class="space-y-1">
                    <label
                        for="transaction-title"
                        class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                    >
                        Judul Transaksi / Keterangan
                    </label>
                    <input
                        v-model="form.title"
                        id="transaction-title"
                        type="text"
                        placeholder="Contoh: Makan Ramen di Mall, Tiket Bioskop, Kopi Sore"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                </div>

                <p
                    v-if="Object.keys(form.errors).length"
                    role="alert"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400"
                >
                    {{ Object.values(form.errors)[0] }}
                </p>

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

        <div
            v-if="isCategoryModalOpen"
            @click.self="isCategoryModalOpen = false"
            class="fixed inset-0 z-[60] flex items-end justify-center bg-black/60 p-4 backdrop-blur-sm sm:items-center"
        >
            <div
                ref="categoryDialogRef"
                @keydown="handleCategoryDialogKeydown"
                role="dialog"
                aria-modal="true"
                aria-labelledby="category-dialog-title"
                tabindex="-1"
                class="w-full max-w-sm space-y-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3
                            id="category-dialog-title"
                            class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Tambah Kategori
                        </h3>
                        <p class="text-[11px] text-zinc-500">
                            Kategori langsung dipilih untuk transaksi ini.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="isCategoryModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup form kategori"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitCategory">
                    <div>
                        <label
                            for="inline-category-name"
                            class="text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                            >Nama Kategori</label
                        >
                        <input
                            id="inline-category-name"
                            v-model="categoryForm.name"
                            type="text"
                            required
                            maxlength="100"
                            placeholder="Contoh: Skincare, Transport Kantor"
                            class="mt-1 min-h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <span
                            class="text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                            >Warna</span
                        >
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="color in categoryColors"
                                :key="color"
                                type="button"
                                @click="categoryForm.color = color"
                                class="flex h-11 w-11 items-center justify-center rounded-full border-4 border-white shadow-sm ring-1 ring-zinc-200 dark:border-zinc-900 dark:ring-zinc-700"
                                :style="{ backgroundColor: color }"
                                :aria-label="`Pilih warna ${color}`"
                            >
                                <Check
                                    v-if="categoryForm.color === color"
                                    class="h-4 w-4 text-white"
                                />
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="categoryForm.hasErrors"
                        role="alert"
                        class="text-xs font-semibold text-rose-600"
                    >
                        {{ Object.values(categoryForm.errors)[0] }}
                    </p>

                    <button
                        type="submit"
                        :disabled="
                            categoryForm.processing || !categoryForm.name.trim()
                        "
                        class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 text-xs font-bold text-white shadow-md transition-colors hover:bg-indigo-500 disabled:opacity-50"
                    >
                        <Plus class="h-4 w-4" />
                        {{
                            categoryForm.processing
                                ? 'Menyimpan...'
                                : 'Tambah & Pilih Kategori'
                        }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
