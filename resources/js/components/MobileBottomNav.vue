<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Home,
    ShoppingBag,
    Heart,
    History,
    User as UserIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import { index as transactionsIndex } from '@/routes/transactions';

const { openModal } = useTransactionModal();

const page = usePage();
const currentUrl = computed(() => page.url);
const homeSectionPaths = [
    '/dashboard',
    '/wishlists',
    '/subscriptions',
    '/budgets',
    '/categories',
    '/couple-space',
    '/settlements',
    '/trips',
];

const isHomeSectionActive = computed(
    () =>
        currentUrl.value === '/' ||
        homeSectionPaths.some((path) => currentUrl.value.startsWith(path)),
);

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
            class="pointer-events-auto mx-auto grid max-w-md grid-cols-5 items-center rounded-[1.75rem] border border-slate-200/80 bg-white/95 px-2 py-2 shadow-xl shadow-slate-900/10 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-900/95"
        >
            <!-- 1. Home -->
            <Link
                href="/dashboard"
                :aria-current="isHomeSectionActive ? 'page' : undefined"
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isHomeSectionActive
                        ? 'font-bold text-indigo-800 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <Home class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Beranda</span>
            </Link>

            <!-- 2. Savings Goals -->
            <Link
                href="/goals"
                :aria-current="isActive('/goals') ? 'page' : undefined"
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/goals')
                        ? 'font-bold text-indigo-800 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <ShoppingBag class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Tabungan</span>
            </Link>

            <!-- 3. Center Hero (+) Quick Add Button (Symmetric & Centered) -->
            <div class="grid w-full place-items-center">
                <button
                    type="button"
                    @click="openModal"
                    class="-mt-8 grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-lg ring-4 shadow-rose-500/30 ring-white transition-[filter,transform] hover:brightness-105 active:scale-95 dark:ring-zinc-900"
                    aria-label="Catat Transaksi Cepat"
                >
                    <Heart class="h-6 w-6 fill-current stroke-[2.5]" />
                </button>
            </div>

            <!-- 4. Transaction History -->
            <Link
                :href="transactionsIndex()"
                :aria-current="isActive('/transactions') ? 'page' : undefined"
                prefetch
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/transactions')
                        ? 'font-bold text-indigo-800 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <History class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Riwayat</span>
            </Link>

            <!-- 5. Profile & Settings -->
            <Link
                href="/settings/profile"
                :aria-current="isActive('/settings') ? 'page' : undefined"
                class="flex min-h-11 w-full flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors"
                :class="
                    isActive('/settings')
                        ? 'font-bold text-indigo-800 dark:text-indigo-400'
                        : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'
                "
            >
                <UserIcon class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Akun</span>
            </Link>
        </nav>
    </div>
</template>
