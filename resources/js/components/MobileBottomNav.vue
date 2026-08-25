<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Home,
    WalletCards,
    Plus,
    History,
    User as UserIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import { index as transactionsIndex } from '@/routes/transactions';

const { openModal } = useTransactionModal();

const page = usePage();
const currentUrl = computed(() => page.url);

function isActive(pattern: string): boolean {
    if (pattern === '/dashboard') {
        return currentUrl.value === '/dashboard' || currentUrl.value === '/';
    }

    return currentUrl.value.startsWith(pattern);
}
</script>

<template>
    <div
        class="pointer-events-none fixed right-0 bottom-0 left-0 z-40 px-4 pt-2 pb-[calc(1rem+env(safe-area-inset-bottom))] md:hidden"
    >
        <nav
            class="pointer-events-auto mx-auto grid max-w-md grid-cols-5 items-center rounded-full border border-zinc-200/80 bg-white/95 px-2 py-1.5 shadow-xl backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-900/95"
        >
            <!-- 1. Home -->
            <Link
                href="/dashboard"
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/dashboard')
                        ? 'font-bold text-indigo-600 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <Home class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Home</span>
            </Link>

            <!-- 2. Wallets -->
            <Link
                href="/wallets"
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/wallets')
                        ? 'font-bold text-indigo-600 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <WalletCards class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Dompet</span>
            </Link>

            <!-- 3. Center Hero (+) Quick Add Button (Symmetric & Centered) -->
            <div class="flex w-full items-center justify-center">
                <button
                    type="button"
                    @click="openModal"
                    class="-mt-7 flex h-13 w-13 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-rose-500 text-white shadow-lg ring-4 shadow-indigo-500/30 ring-white transition-transform hover:scale-105 active:scale-90 dark:ring-zinc-900"
                    aria-label="Catat Transaksi Cepat"
                >
                    <Plus class="h-6 w-6 stroke-[2.5]" />
                </button>
            </div>

            <!-- 4. Transaction History -->
            <Link
                :href="transactionsIndex()"
                prefetch
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/transactions')
                        ? 'font-bold text-indigo-600 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <History class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Riwayat</span>
            </Link>

            <!-- 5. Profile & Settings -->
            <Link
                href="/settings/profile"
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/settings')
                        ? 'font-bold text-indigo-600 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <UserIcon class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Profil</span>
            </Link>
        </nav>
    </div>
</template>
