<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Home, WalletCards, Plus, Handshake, User as UserIcon } from '@lucide/vue';
import { useTransactionModal } from '@/composables/useTransactionModal';

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
    <div class="fixed bottom-0 left-0 right-0 z-40 px-4 pb-4 pt-2 pointer-events-none md:hidden">
        <nav
            class="pointer-events-auto mx-auto grid grid-cols-5 items-center max-w-md rounded-full border border-zinc-200/80 bg-white/95 px-2 py-1.5 shadow-xl backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-900/95"
        >
            <!-- 1. Home -->
            <Link
                href="/dashboard"
                class="flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors w-full"
                :class="isActive('/dashboard') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
            >
                <Home class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Home</span>
            </Link>

            <!-- 2. Wallets -->
            <Link
                href="/wallets"
                class="flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors w-full"
                :class="isActive('/wallets') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
            >
                <WalletCards class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Dompet</span>
            </Link>

            <!-- 3. Center Hero (+) Quick Add Button (Symmetric & Centered) -->
            <div class="flex items-center justify-center w-full">
                <button
                    type="button"
                    @click="openModal"
                    class="-mt-7 flex h-13 w-13 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-rose-500 text-white shadow-lg shadow-indigo-500/30 ring-4 ring-white dark:ring-zinc-900 transition-transform active:scale-90 hover:scale-105"
                    aria-label="Catat Transaksi Cepat"
                >
                    <Plus class="h-6 w-6 stroke-[2.5]" />
                </button>
            </div>

            <!-- 4. Settlements / Split -->
            <Link
                href="/settlements"
                class="flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors w-full"
                :class="isActive('/settlements') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
            >
                <Handshake class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Talangan</span>
            </Link>

            <!-- 5. Profile & Settings -->
            <Link
                href="/settings/profile"
                class="flex flex-col items-center justify-center gap-0.5 rounded-2xl py-1 text-center transition-colors w-full"
                :class="isActive('/settings') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
            >
                <UserIcon class="h-5 w-5" />
                <span class="text-[10px] leading-tight">Profil</span>
            </Link>
        </nav>
    </div>
</template>
