<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Handshake, CheckCircle2, History, Sparkles, User as UserIcon } from '@lucide/vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import TransactionDrawer from '@/components/TransactionDrawer.vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import type { Settlement, Wallet, Category } from '@/types/finance';
import type { User } from '@/types/auth';

const props = withDefaults(
    defineProps<{
        unsettled?: {
            net_balance: number;
            debtor_id: number | null;
            creditor_id: number | null;
            debtor_name: string | null;
            creditor_name: string | null;
            amount_owed: number;
            user_one_balance: number;
            user_two_balance: number;
            unsettled_splits_count: number;
        };
        history?: {
            data: Settlement[];
            links: any[];
        } | Settlement[];
        wallets?: Wallet[];
        categories?: Category[];
        partner?: User | null;
        auth: {
            user: User;
        };
    }>(),
    {
        unsettled: () => ({
            net_balance: 0,
            debtor_id: null,
            creditor_id: null,
            debtor_name: null,
            creditor_name: null,
            amount_owed: 0,
            user_one_balance: 0,
            user_two_balance: 0,
            unsettled_splits_count: 0,
        }),
        history: () => ({ data: [], links: [] }),
    }
);

const historyItems = computed(() => {
    if (!props.history) return [];
    if (Array.isArray(props.history)) return props.history;
    return props.history.data || [];
});

const isSettleModalOpen = ref(false);
const { isOpen: isDrawerOpen } = useTransactionModal();

const settleForm = useForm({
    amount: props.unsettled?.amount_owed || 0,
    to_user_id: props.unsettled?.creditor_id || 0,
    payment_method: 'Transfer Bank',
    notes: 'Pelunasan talangan kencan',
});

function handleSettle() {
    settleForm.amount = props.unsettled?.amount_owed || 0;
    settleForm.to_user_id = props.unsettled?.creditor_id || 0;
    settleForm.post('/settlements', {
        preserveScroll: true,
        onSuccess: () => {
            isSettleModalOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Talangan & Split Bill - Couple Finance" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 antialiased pb-28 dark:bg-zinc-950 dark:text-zinc-100">
        <!-- Header -->
        <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/80 px-4 py-3.5 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        Talangan & Split Bill
                    </h1>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 pt-5 space-y-6">
            <!-- Debt Hero Card -->
            <div class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800">
                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-zinc-400">
                    <Handshake class="h-4 w-4 text-amber-400" />
                    <span>Status Saldo Talangan Kencan</span>
                </div>

                <div v-if="unsettled && unsettled.amount_owed > 0" class="mt-4 space-y-4">
                    <div>
                        <span class="text-xs text-zinc-400">
                            {{ unsettled.debtor_id === auth.user.id ? 'Kamu berhutang ke pasangan sebesar' : `${unsettled.debtor_name} berhutang ke kamu sebesar` }}
                        </span>
                        <div class="text-3xl sm:text-4xl font-extrabold text-amber-400 mt-1">
                            Rp {{ Number(unsettled.amount_owed).toLocaleString() }}
                        </div>
                    </div>

                    <p class="text-xs text-zinc-400">
                        Dari total <span class="font-bold text-white">{{ unsettled.unsettled_splits_count }} transaksi kencan</span> yang ditalangi.
                    </p>

                    <button
                        v-if="unsettled.debtor_id === auth.user.id"
                        type="button"
                        @click="handleSettle"
                        :disabled="settleForm.processing"
                        class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-2xl bg-amber-500 px-6 py-3 text-xs font-bold text-zinc-950 shadow-md shadow-amber-500/20 hover:bg-amber-400 transition-all"
                    >
                        <CheckCircle2 class="h-4 w-4" /> Settle Up (Lunaskan Sekarang)
                    </button>
                </div>

                <div v-else class="mt-4 flex items-center gap-3 py-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-400">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Semua Lunas & Seimbang! 🎉</h3>
                        <p class="text-xs text-zinc-400">Tidak ada hutang atau talangan kencan yang tertunda saat ini.</p>
                    </div>
                </div>
            </div>

            <!-- Settlement History List -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <History class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Riwayat Pelunasan (Settlements)</h2>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 bg-white divide-y divide-zinc-100 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:divide-zinc-800">
                    <div
                        v-for="st in historyItems"
                        :key="st.id"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ st.from_user?.nickname || st.from_user?.name?.split(' ')[0] }} melunasi ke {{ st.to_user?.nickname || st.to_user?.name?.split(' ')[0] }}
                                </h3>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ st.payment_method }} • {{ new Date(st.settled_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </p>
                            </div>
                        </div>

                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                            Rp {{ Number(st.amount).toLocaleString() }}
                        </span>
                    </div>

                    <div
                        v-if="historyItems.length === 0"
                        class="p-6 text-center text-xs text-zinc-500"
                    >
                        Belum ada riwayat pelunasan.
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
