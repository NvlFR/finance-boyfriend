<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import TransactionDrawer from '@/components/TransactionDrawer.vue';
import { useTransactionModal } from '@/composables/useTransactionModal';
import type { BreadcrumbItem } from '@/types';
import type { User } from '@/types/auth';
import type { Wallet, Category } from '@/types/finance';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user as User);
const partner = computed(() => (page.props as any).partner as User | undefined);
const wallets = computed(() => ((page.props as any).wallets || []) as Wallet[]);
const categories = computed(() => ((page.props as any).categories || []) as Category[]);

const { isOpen: isDrawerOpen } = useTransactionModal();
const isNavigating = ref(false);

let removeStartListener: (() => void) | null = null;
let removeFinishListener: (() => void) | null = null;

onMounted(() => {
    removeStartListener = router.on('start', () => {
        isNavigating.value = true;
    });

    removeFinishListener = router.on('finish', () => {
        isNavigating.value = false;
    });
});

onUnmounted(() => {
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
});
</script>

<template>
    <div class="min-h-screen bg-zinc-50 text-zinc-900 antialiased pb-28 dark:bg-zinc-950 dark:text-zinc-100 selection:bg-rose-500 selection:text-white">
        <!-- Top Loading Progress Bar (Glowing Gradient Line) -->
        <div
            v-if="isNavigating"
            class="fixed top-0 left-0 right-0 z-50 h-1 bg-gradient-to-r from-indigo-500 via-rose-500 to-amber-500 animate-pulse shadow-sm shadow-rose-500/50"
        />

        <!-- Page Content Slot with Smooth Transition -->
        <main class="mx-auto max-w-5xl px-4 py-5">
            <slot />
        </main>

        <!-- Global Persistent Bottom Navigation Dock (Never reloads between page transitions) -->
        <MobileBottomNav />

        <!-- Global Transaction Drawer Modal -->
        <TransactionDrawer
            v-if="user"
            v-model:open="isDrawerOpen"
            :wallets="wallets"
            :categories="categories"
            :user="user"
            :partner="partner"
            @created="isDrawerOpen = false"
        />
    </div>
</template>

<style>
/* Smooth SPA Page Transition */
.page-enter-active,
.page-leave-active {
    transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1), transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

.page-enter-from {
    opacity: 0;
    transform: translateY(6px);
}

.page-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
