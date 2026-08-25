<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Handshake,
    CheckCircle2,
    History,
    AlertTriangle,
    X,
    ArrowRight,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import { store as settlementStore } from '@/routes/settlements';
import type { User } from '@/types/auth';
import type { Settlement, Wallet, Category } from '@/types/finance';

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
        history?:
            | {
                  data: Settlement[];
                  links: any[];
              }
            | Settlement[];
        unsettledItems?: Array<{
            id: number;
            title: string;
            amount: number | string;
            transaction_date: string;
            paid_by_name: string;
            user_one_amount: number | string;
            user_two_amount: number | string;
            split_type: string;
        }>;
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
        unsettledItems: () => [],
    },
);

const historyItems = computed(() => {
    if (!props.history) {
        return [];
    }

    if (Array.isArray(props.history)) {
        return props.history;
    }

    return props.history.data || [];
});

const isSettleModalOpen = ref(false);

const settleForm = useForm({
    amount: props.unsettled?.amount_owed || 0,
    to_user_id: props.unsettled?.creditor_id || 0,
    payment_method: 'Transfer Bank',
    notes: 'Pelunasan talangan kencan',
});

function handleSettle() {
    settleForm.amount = props.unsettled?.amount_owed || 0;
    settleForm.to_user_id = props.unsettled?.creditor_id || 0;
    settleForm.post(settlementStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            isSettleModalOpen.value = false;
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <Head title="Talangan & Split Bill - Couple Finance" />
        <!-- Header -->
        <header
            class="border-b border-zinc-200/80 py-2 dark:border-zinc-800/80"
        >
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1
                        class="text-base font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Talangan & Split Bill
                    </h1>
                </div>
            </div>
        </header>

        <main class="space-y-6">
            <!-- Debt Hero Card -->
            <div
                class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800"
            >
                <div
                    class="flex items-center gap-2 text-xs font-medium tracking-wider text-zinc-400 uppercase"
                >
                    <Handshake class="h-4 w-4 text-amber-400" />
                    <span>Status Saldo Talangan Kencan</span>
                </div>

                <div
                    v-if="unsettled && unsettled.amount_owed > 0"
                    class="mt-4 space-y-4"
                >
                    <div>
                        <span class="text-xs text-zinc-400">
                            {{
                                unsettled.debtor_id === auth.user.id
                                    ? 'Kamu berhutang ke pasangan sebesar'
                                    : `${unsettled.debtor_name} berhutang ke kamu sebesar`
                            }}
                        </span>
                        <div
                            class="mt-1 text-3xl font-extrabold text-amber-400 sm:text-4xl"
                        >
                            Rp
                            {{ Number(unsettled.amount_owed).toLocaleString() }}
                        </div>
                    </div>

                    <p class="text-xs text-zinc-400">
                        Dari total
                        <span class="font-bold text-white"
                            >{{ unsettled.unsettled_splits_count }} transaksi
                            kencan</span
                        >
                        yang ditalangi.
                    </p>

                    <button
                        v-if="unsettled.debtor_id === auth.user.id"
                        type="button"
                        @click="isSettleModalOpen = true"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-amber-500 px-6 py-3 text-xs font-bold text-zinc-950 shadow-md shadow-amber-500/20 transition-all hover:bg-amber-400 sm:w-auto"
                    >
                        <CheckCircle2 class="h-4 w-4" /> Tinjau Pelunasan
                    </button>
                </div>

                <div v-else class="mt-4 flex items-center gap-3 py-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-400"
                    >
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">
                            Semua Lunas & Seimbang! 🎉
                        </h3>
                        <p class="text-xs text-zinc-400">
                            Tidak ada hutang atau talangan kencan yang tertunda
                            saat ini.
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="unsettledItems.length > 0" class="space-y-3">
                <div>
                    <h2
                        class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Rincian Talangan Belum Lunas
                    </h2>
                    <p class="text-[11px] text-zinc-500">
                        Nominal pelunasan di atas adalah hasil bersih setelah
                        talangan kalian saling dikurangi.
                    </p>
                </div>
                <div
                    class="divide-y divide-zinc-100 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div
                        v-for="item in unsettledItems"
                        :key="item.id"
                        class="flex items-start justify-between gap-3 p-4"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100"
                            >
                                {{ item.title }}
                            </p>
                            <p class="mt-1 text-[11px] text-zinc-500">
                                Dibayar {{ item.paid_by_name }} ·
                                {{
                                    new Date(
                                        item.transaction_date,
                                    ).toLocaleDateString('id-ID', {
                                        day: 'numeric',
                                        month: 'short',
                                        year: 'numeric',
                                    })
                                }}
                            </p>
                        </div>
                        <p
                            class="shrink-0 text-xs font-black text-zinc-900 dark:text-zinc-100"
                        >
                            Rp {{ Number(item.amount).toLocaleString('id-ID') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Settlement History List -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <History class="h-4 w-4 text-indigo-500" />
                    <h2
                        class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Riwayat Pelunasan (Settlements)
                    </h2>
                </div>

                <div
                    class="divide-y divide-zinc-100 rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div
                        v-for="st in historyItems"
                        :key="st.id"
                        class="flex items-center justify-between p-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            <div>
                                <h3
                                    class="text-xs font-semibold text-zinc-900 dark:text-zinc-100"
                                >
                                    {{
                                        st.from_user?.nickname ||
                                        st.from_user?.name?.split(' ')[0]
                                    }}
                                    melunasi ke
                                    {{
                                        st.to_user?.nickname ||
                                        st.to_user?.name?.split(' ')[0]
                                    }}
                                </h3>
                                <p
                                    class="text-[11px] text-zinc-500 dark:text-zinc-400"
                                >
                                    {{ st.payment_method }} •
                                    {{
                                        new Date(
                                            st.settled_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                        })
                                    }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="text-xs font-bold text-emerald-600 dark:text-emerald-400"
                        >
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

        <div
            v-if="isSettleModalOpen && unsettled"
            @click.self="isSettleModalOpen = false"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-4 backdrop-blur-sm sm:items-center"
        >
            <div
                class="w-full max-w-md space-y-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2
                            class="text-base font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Konfirmasi Pelunasan Talangan
                        </h2>
                        <p class="mt-1 text-xs text-zinc-500">
                            Periksa detailnya sebelum menandai seluruh talangan
                            sebagai lunas.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="isSettleModalOpen = false"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup konfirmasi pelunasan"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="rounded-2xl bg-zinc-950 p-4 text-white">
                    <div
                        class="flex items-center justify-between gap-2 text-xs font-semibold"
                    >
                        <span>{{ unsettled.debtor_name }}</span>
                        <ArrowRight class="h-4 w-4 text-amber-400" />
                        <span>{{ unsettled.creditor_name }}</span>
                    </div>
                    <p
                        class="mt-3 text-center text-2xl font-black text-amber-400"
                    >
                        Rp
                        {{
                            Number(unsettled.amount_owed).toLocaleString(
                                'id-ID',
                            )
                        }}
                    </p>
                    <p class="mt-1 text-center text-[11px] text-zinc-400">
                        Melunasi
                        {{ unsettled.unsettled_splits_count }} transaksi
                        talangan sekaligus
                    </p>
                </div>

                <div
                    class="flex gap-2 rounded-2xl border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300"
                >
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <p class="text-[11px] leading-relaxed">
                        Tombol konfirmasi hanya mencatat bahwa pembayaran sudah
                        dilakukan. Saldo dompet aplikasi tidak dipindahkan
                        otomatis.
                    </p>
                </div>

                <div>
                    <label
                        for="settlement-payment-method"
                        class="text-xs font-semibold text-zinc-700 dark:text-zinc-300"
                        >Metode Pembayaran</label
                    >
                    <input
                        id="settlement-payment-method"
                        v-model="settleForm.payment_method"
                        type="text"
                        required
                        class="mt-1 min-h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 focus:border-amber-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <p
                    v-if="settleForm.hasErrors"
                    role="alert"
                    class="text-xs font-semibold text-rose-600"
                >
                    {{ Object.values(settleForm.errors)[0] }}
                </p>

                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="isSettleModalOpen = false"
                        class="min-h-12 rounded-2xl border border-zinc-200 text-xs font-bold text-zinc-700 dark:border-zinc-700 dark:text-zinc-300"
                    >
                        Belum Dibayar
                    </button>
                    <button
                        type="button"
                        @click="handleSettle"
                        :disabled="settleForm.processing"
                        class="min-h-12 rounded-2xl bg-amber-500 px-3 text-xs font-bold text-zinc-950 shadow-md transition-colors hover:bg-amber-400 disabled:opacity-50"
                    >
                        {{
                            settleForm.processing
                                ? 'Mencatat...'
                                : 'Sudah Dibayar, Lunaskan'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
