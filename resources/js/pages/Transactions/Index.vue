<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, ArrowUpRight, ArrowDownLeft, ArrowRightLeft, Filter, Trash2 } from '@lucide/vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import TransactionDrawer from '@/components/TransactionDrawer.vue';
import type { Transaction, Category, Wallet } from '@/types/finance';
import type { User } from '@/types/auth';

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: any[];
        total: number;
    };
    filters: {
        scope?: string;
        type?: string;
        category_id?: string;
        wallet_id?: string;
        start_date?: string;
        end_date?: string;
    };
    wallets?: Wallet[];
    categories?: Category[];
    auth: {
        user: User;
    };
}>();

const isDrawerOpen = ref(false);

function deleteTransaction(id: number) {
    if (confirm('Yakin ingin menghapus transaksi ini? Saldo dompet akan disesuaikan kembali.')) {
        router.delete(`/transactions/${id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Riwayat Transaksi - Couple Finance" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 antialiased pb-28 dark:bg-zinc-950 dark:text-zinc-100">
        <!-- Header -->
        <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/80 px-4 py-3.5 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        Riwayat Transaksi
                    </h1>
                </div>

                <button
                    type="button"
                    @click="isDrawerOpen = true"
                    class="flex items-center gap-1 rounded-full bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
                >
                    <Plus class="h-4 w-4" /> Catat
                </button>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 pt-5 space-y-4">
            <!-- Transactions Feed -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white divide-y divide-zinc-100 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:divide-zinc-800">
                <div
                    v-for="tx in transactions.data"
                    :key="tx.id"
                    class="flex items-center justify-between p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-white"
                            :style="{ backgroundColor: tx.category?.color || '#6366F1' }"
                        >
                            <TrendingDown v-if="tx.type === 'expense'" class="h-5 w-5" />
                            <TrendingUp v-else-if="tx.type === 'income'" class="h-5 w-5" />
                            <ArrowRightLeft v-else class="h-5 w-5" />
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ tx.title || tx.category?.name || 'Transaksi' }}
                            </h3>
                            <div class="flex items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                                <span>{{ tx.wallet?.name }}</span>
                                <span>•</span>
                                <span>{{ tx.user?.nickname || tx.user?.name?.split(' ')[0] }}</span>
                                <span v-if="tx.scope === 'shared'" class="rounded bg-rose-500/10 px-1.5 py-0.5 text-[9px] font-medium text-rose-600 dark:text-rose-400">
                                    Kencan
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <span
                                class="text-xs font-bold"
                                :class="[
                                    tx.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-zinc-100'
                                ]"
                            >
                                {{ tx.type === 'expense' ? '-' : (tx.type === 'income' ? '+' : '') }}
                                Rp {{ Number(tx.amount).toLocaleString() }}
                            </span>
                            <p class="text-[10px] text-zinc-400">
                                {{ new Date(tx.transaction_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="deleteTransaction(tx.id)"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                            title="Hapus Transaksi"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div
                    v-if="transactions.data.length === 0"
                    class="p-8 text-center text-xs text-zinc-500"
                >
                    Belum ada riwayat transaksi yang tercatat.
                </div>
            </div>
        </main>
    </div>
</template>
