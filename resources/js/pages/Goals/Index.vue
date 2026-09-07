<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Target,
    Plus,
    Sparkles,
    CheckCircle2,
    Trophy,
    Coins,
    X,
    Edit2,
    Trash2,
    UserRound,
    UsersRound,
    LockKeyhole,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import FormErrorSummary from '@/components/FormErrorSummary.vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import {
    contribute as goalContribute,
    destroy as goalDestroy,
    store as goalStore,
    update as goalUpdate,
} from '@/routes/goals';
import type { User } from '@/types/auth';
import type { Wallet, Category } from '@/types/finance';

type Goal = {
    id: number;
    created_by_user_id: number;
    scope: 'personal' | 'shared';
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
const goalToDelete = ref<Goal | null>(null);
const isDeleting = ref(false);
const activeScope = ref<'all' | 'personal' | 'shared'>('all');
const isAnyModalOpen = computed(
    () =>
        isCreateModalOpen.value ||
        isEditModalOpen.value ||
        isContributeModalOpen.value,
);

function closeActiveModal(): void {
    isCreateModalOpen.value = false;
    isEditModalOpen.value = false;
    isContributeModalOpen.value = false;
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    isAnyModalOpen,
    closeActiveModal,
);

const createForm = useForm({
    scope: 'personal' as 'personal' | 'shared',
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
    wallet_id: props.wallets?.[0]?.id || null,
    notes: 'Setoran tabungan',
    client_reference: '',
});

function createClientReference(): string {
    return typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
        ? crypto.randomUUID()
        : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

const colors = [
    '#6366F1',
    '#EC4899',
    '#10B981',
    '#F59E0B',
    '#3B82F6',
    '#8B5CF6',
    '#14B8A6',
];

const visibleGoals = computed(() => {
    if (activeScope.value === 'all') {
        return props.goals;
    }

    return props.goals.filter((goal) => goal.scope === activeScope.value);
});

const visibleTotalSaved = computed(() =>
    visibleGoals.value.reduce(
        (total, goal) => total + Number(goal.current_amount),
        0,
    ),
);
const visibleTotalTarget = computed(() =>
    visibleGoals.value.reduce(
        (total, goal) => total + Number(goal.target_amount),
        0,
    ),
);
const personalGoalsCount = computed(
    () => props.goals.filter((goal) => goal.scope === 'personal').length,
);
const sharedGoalsCount = computed(
    () => props.goals.filter((goal) => goal.scope === 'shared').length,
);
const availableContributionWallets = computed(() => {
    if (selectedGoal.value?.scope !== 'personal') {
        return props.wallets;
    }

    return props.wallets.filter(
        (wallet) =>
            wallet.type === 'personal' && wallet.user_id === props.auth.user.id,
    );
});

function canManageGoal(goal: Goal): boolean {
    return (
        goal.scope === 'shared' ||
        goal.created_by_user_id === props.auth.user.id
    );
}

function ownerLabel(goal: Goal): string {
    if (goal.scope === 'shared') {
        return 'Bersama';
    }

    if (goal.created_by_user_id === props.auth.user.id) {
        return 'Pribadi Kamu';
    }

    return `Pribadi ${goal.created_by_user?.nickname || goal.created_by_user?.name || 'Pasangan'}`;
}

function openContributeModal(goal: Goal) {
    contributeForm.clearErrors();
    selectedGoal.value = goal;
    contributeForm.amount = '';
    contributeForm.wallet_id =
        availableContributionWallets.value[0]?.id || null;
    contributeForm.client_reference = createClientReference();
    isContributeModalOpen.value = true;
}

function openEditModal(goal: Goal) {
    editForm.clearErrors();
    editingGoal.value = goal;
    editForm.name = goal.name;
    editForm.target_amount = goal.target_amount;
    editForm.target_date = goal.target_date || '';
    editForm.color = goal.color || '#6366F1';
    isEditModalOpen.value = true;
}

function submitCreate() {
    const createdScope = createForm.scope;

    createForm.post(goalStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            activeScope.value = createdScope;
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function openCreateModal(): void {
    createForm.clearErrors();
    createForm.scope = activeScope.value === 'shared' ? 'shared' : 'personal';
    isCreateModalOpen.value = true;
}

function submitEdit() {
    if (!editingGoal.value) {
        return;
    }

    editForm.put(goalUpdate.url(editingGoal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingGoal.value = null;
        },
    });
}

function submitContribute() {
    if (!selectedGoal.value) {
        return;
    }

    contributeForm.post(goalContribute.url(selectedGoal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            contributeForm.reset();
            isContributeModalOpen.value = false;
        },
    });
}

function deleteGoal(goal: Goal) {
    goalToDelete.value = goal;
}

function confirmDeleteGoal(): void {
    if (!goalToDelete.value) {
        return;
    }

    router.delete(goalDestroy.url(goalToDelete.value.id), {
        preserveScroll: true,
        onStart: () => (isDeleting.value = true),
        onSuccess: () => (goalToDelete.value = null),
        onFinish: () => (isDeleting.value = false),
    });
}
</script>

<template>
    <Head title="Tabungan - Couple Finance" />

    <div class="space-y-6">
        <!-- Top Bar Action -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="min-w-0">
                <h1
                    class="text-base font-bold text-zinc-900 dark:text-zinc-100"
                >
                    Tabungan
                </h1>
                <p class="text-xs text-zinc-500">
                    Pisahkan target pribadi dan impian bersama
                </p>
            </div>

            <button
                type="button"
                @click="openCreateModal"
                class="flex min-h-11 items-center gap-1.5 rounded-full bg-gradient-to-r from-indigo-600 to-rose-500 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:opacity-90"
            >
                <Plus class="h-4 w-4" /> Buat Target
            </button>
        </div>

        <!-- Summary Progress Banner -->
        <div
            class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-900/90 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800"
        >
            <div class="flex items-center justify-between">
                <div>
                    <span
                        class="text-xs font-medium tracking-wider text-indigo-300 uppercase"
                        >Total Terkumpul</span
                    >
                    <div
                        class="text-2xl font-extrabold tracking-tight sm:text-3xl"
                    >
                        Rp {{ visibleTotalSaved.toLocaleString('id-ID') }}
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-zinc-400">Total Target</span>
                    <p class="text-sm font-semibold text-zinc-200">
                        Rp {{ visibleTotalTarget.toLocaleString('id-ID') }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-1.5 flex justify-between text-xs text-zinc-400">
                    <span>Kemajuan Keseluruhan</span>
                    <span class="font-bold text-white">
                        {{
                            visibleTotalTarget > 0
                                ? Math.min(
                                      100,
                                      Math.round(
                                          (visibleTotalSaved /
                                              visibleTotalTarget) *
                                              100,
                                      ),
                                  )
                                : 0
                        }}%
                    </span>
                </div>
                <div
                    class="h-3 w-full overflow-hidden rounded-full bg-white/10"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-rose-500 transition-all duration-500"
                        :style="{
                            width: `${visibleTotalTarget > 0 ? Math.min(100, Math.round((visibleTotalSaved / visibleTotalTarget) * 100)) : 0}%`,
                        }"
                    />
                </div>
            </div>
        </div>

        <div
            role="group"
            aria-label="Filter jenis tabungan"
            class="grid grid-cols-3 gap-1 rounded-2xl bg-zinc-100 p-1 dark:bg-zinc-800"
        >
            <button
                type="button"
                :aria-pressed="activeScope === 'all'"
                class="min-h-11 rounded-xl px-2 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                :class="
                    activeScope === 'all'
                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-zinc-700 dark:text-indigo-300'
                        : 'text-zinc-500 dark:text-zinc-400'
                "
                @click="activeScope = 'all'"
            >
                Semua ({{ goals.length }})
            </button>
            <button
                type="button"
                :aria-pressed="activeScope === 'personal'"
                class="min-h-11 rounded-xl px-2 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                :class="
                    activeScope === 'personal'
                        ? 'bg-white text-indigo-600 shadow-sm dark:bg-zinc-700 dark:text-indigo-300'
                        : 'text-zinc-500 dark:text-zinc-400'
                "
                @click="activeScope = 'personal'"
            >
                Pribadi ({{ personalGoalsCount }})
            </button>
            <button
                type="button"
                :aria-pressed="activeScope === 'shared'"
                class="min-h-11 rounded-xl px-2 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                :class="
                    activeScope === 'shared'
                        ? 'bg-white text-rose-600 shadow-sm dark:bg-zinc-700 dark:text-rose-300'
                        : 'text-zinc-500 dark:text-zinc-400'
                "
                @click="activeScope = 'shared'"
            >
                Bersama ({{ sharedGoalsCount }})
            </button>
        </div>

        <!-- Goals Grid -->
        <div class="space-y-4">
            <div
                v-for="goal in visibleGoals"
                :key="goal.id"
                class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-sm"
                            :style="{
                                backgroundColor: goal.color || '#6366F1',
                            }"
                        >
                            <Target class="h-6 w-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <h3
                                    class="text-base font-bold break-words text-zinc-900 dark:text-zinc-100"
                                >
                                    {{ goal.name }}
                                </h3>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="
                                        goal.scope === 'shared'
                                            ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                                            : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400'
                                    "
                                >
                                    <UsersRound
                                        v-if="goal.scope === 'shared'"
                                        class="h-3 w-3"
                                    />
                                    <UserRound v-else class="h-3 w-3" />
                                    {{ ownerLabel(goal) }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500">
                                Target:
                                {{
                                    goal.target_date
                                        ? new Date(
                                              goal.target_date,
                                          ).toLocaleDateString('id-ID', {
                                              month: 'long',
                                              year: 'numeric',
                                          })
                                        : 'Fleksibel'
                                }}
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
                            v-if="canManageGoal(goal)"
                            type="button"
                            @click="openEditModal(goal)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            title="Edit Target"
                        >
                            <Edit2 class="h-4 w-4" />
                        </button>

                        <button
                            v-if="canManageGoal(goal)"
                            type="button"
                            @click="deleteGoal(goal)"
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Hapus Target"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span
                            class="font-bold text-indigo-600 dark:text-indigo-400"
                        >
                            Rp
                            {{
                                Number(goal.current_amount).toLocaleString(
                                    'id-ID',
                                )
                            }}
                        </span>
                        <span class="text-zinc-500">
                            dari Rp
                            {{
                                Number(goal.target_amount).toLocaleString(
                                    'id-ID',
                                )
                            }}
                        </span>
                    </div>
                    <div
                        class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                    >
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
                <div
                    class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800"
                >
                    <span class="text-xs text-zinc-400">
                        {{
                            Math.min(
                                100,
                                Math.round(
                                    (Number(goal.current_amount) /
                                        Number(goal.target_amount)) *
                                        100,
                                ),
                            )
                        }}% Terkumpul
                    </span>

                    <button
                        v-if="canManageGoal(goal)"
                        type="button"
                        @click="openContributeModal(goal)"
                        class="flex min-h-11 items-center gap-1 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition-colors hover:bg-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
                    >
                        <Coins class="h-3.5 w-3.5" /> + Setor Tabungan
                    </button>
                    <span
                        v-else
                        class="inline-flex min-h-11 items-center gap-1.5 text-xs font-medium text-zinc-400"
                    >
                        <LockKeyhole class="h-3.5 w-3.5" /> Hanya pemilik
                    </span>
                </div>
            </div>

            <div
                v-if="visibleGoals.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800"
            >
                Belum ada target tabungan di pilihan ini. Klik tombol "Buat
                Target" untuk memulai!
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            @click.self="isCreateModalOpen = false"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-goal-title"
                tabindex="-1"
                @keydown="handleDialogKeydown"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="create-goal-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Buat Target Tabungan Impian
                    </h2>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup dialog buat target"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                    <fieldset>
                        <legend class="block text-xs font-medium text-zinc-500">
                            Jenis Tabungan
                        </legend>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="min-h-20 rounded-2xl border p-3 text-left transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                                :class="
                                    createForm.scope === 'personal'
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500/20 dark:bg-indigo-950/40 dark:text-indigo-300'
                                        : 'border-zinc-200 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300'
                                "
                                :aria-pressed="createForm.scope === 'personal'"
                                @click="createForm.scope = 'personal'"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-xs font-bold"
                                >
                                    <UserRound class="h-4 w-4" /> Pribadi
                                </span>
                                <span class="mt-1 block text-[10px] opacity-75">
                                    Hanya kamu yang mengelola
                                </span>
                            </button>
                            <button
                                type="button"
                                class="min-h-20 rounded-2xl border p-3 text-left transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-500"
                                :class="
                                    createForm.scope === 'shared'
                                        ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-500/20 dark:bg-rose-950/40 dark:text-rose-300'
                                        : 'border-zinc-200 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300'
                                "
                                :aria-pressed="createForm.scope === 'shared'"
                                @click="createForm.scope = 'shared'"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-xs font-bold"
                                >
                                    <UsersRound class="h-4 w-4" /> Bersama
                                </span>
                                <span class="mt-1 block text-[10px] opacity-75">
                                    Bisa dikelola berdua
                                </span>
                            </button>
                        </div>
                    </fieldset>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nama Impian</label
                        >
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Liburan ke Jepang, Dana Pernikahan"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Target Nominal (Rp)</label
                        >
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
                        <label class="block text-xs font-medium text-zinc-500"
                            >Target Tanggal (Opsional)</label
                        >
                        <input
                            v-model="createForm.target_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
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
                                <CheckCircle2
                                    v-if="createForm.color === c"
                                    class="h-4 w-4 text-white"
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
                                : 'Simpan Impian'
                        }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingGoal"
            @click.self="isEditModalOpen = false"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-goal-title"
                tabindex="-1"
                @keydown="handleDialogKeydown"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="edit-goal-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Edit Target Tabungan
                    </h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup dialog edit target"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nama Impian</label
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
                            >Target Nominal (Rp)</label
                        >
                        <input
                            v-model="editForm.target_amount"
                            type="number"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Target Tanggal</label
                        >
                        <input
                            v-model="editForm.target_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
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
                                <CheckCircle2
                                    v-if="editForm.color === c"
                                    class="h-4 w-4 text-white"
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

        <!-- Contribute Modal -->
        <div
            v-if="isContributeModalOpen && selectedGoal"
            @click.self="isContributeModalOpen = false"
            class="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                ref="dialogRef"
                @click.stop
                role="dialog"
                aria-modal="true"
                aria-labelledby="contribute-goal-title"
                tabindex="-1"
                @keydown="handleDialogKeydown"
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md cursor-default overflow-y-auto rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <h2
                        id="contribute-goal-title"
                        class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        Setor Tabungan
                    </h2>
                    <button
                        type="button"
                        @click="isContributeModalOpen = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup dialog setor tabungan"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitContribute" class="mt-4 space-y-4">
                    <div
                        class="rounded-2xl bg-indigo-500/10 p-3 text-xs text-indigo-700 dark:text-indigo-300"
                    >
                        Menabung untuk:
                        <strong class="text-zinc-900 dark:text-zinc-100">{{
                            selectedGoal.name
                        }}</strong>
                    </div>

                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300"
                    >
                        Jika memilih dompet, saldo dompet akan berkurang dan
                        berpindah ke target tabungan. Total kekayaan tidak
                        berkurang.
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Nominal Setoran (Rp)</label
                        >
                        <input
                            v-model="contributeForm.amount"
                            type="number"
                            placeholder="Contoh: 100000"
                            required
                            min="1000"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div v-if="availableContributionWallets.length > 0">
                        <label class="block text-xs font-medium text-zinc-500"
                            >Potong dari Dompet (Opsional)</label
                        >
                        <select
                            v-model="contributeForm.wallet_id"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option :value="null">
                                -- Tanpa Potong Saldo Dompet --
                            </option>
                            <option
                                v-for="w in availableContributionWallets"
                                :key="w.id"
                                :value="w.id"
                            >
                                {{ w.name }} ·
                                {{
                                    w.type === 'joint'
                                        ? 'Bersama'
                                        : w.user?.nickname ||
                                          w.user?.name ||
                                          'Pribadi'
                                }}
                                (Rp
                                {{ Number(w.balance).toLocaleString('id-ID') }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500"
                            >Catatan / Pesan</label
                        >
                        <input
                            v-model="contributeForm.notes"
                            type="text"
                            placeholder="Contoh: Setoran gajian bulan ini"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <FormErrorSummary :errors="contributeForm.errors" />

                    <button
                        type="submit"
                        :disabled="
                            contributeForm.processing || !contributeForm.amount
                        "
                        class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 py-3 text-xs font-bold text-white shadow-md transition-all hover:opacity-90"
                    >
                        {{
                            contributeForm.processing
                                ? 'Memproses...'
                                : 'Konfirmasi Setor'
                        }}
                    </button>
                </form>
            </div>
        </div>

        <ConfirmActionDialog
            :open="goalToDelete !== null"
            title="Hapus target tabungan?"
            :description="`Target ${goalToDelete?.name || ''} beserta riwayat setorannya akan dihapus.`"
            :processing="isDeleting"
            @update:open="goalToDelete = null"
            @confirm="confirmDeleteGoal"
        />
    </div>
</template>
