<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';
import TransactionDrawer from '@/components/TransactionDrawer.vue';
import { Toaster } from '@/components/ui/sonner';
import { useTransactionModal } from '@/composables/useTransactionModal';
import type { BreadcrumbItem } from '@/types';
import type { User } from '@/types/auth';
import type { Wallet, Category } from '@/types/finance';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user as User);
const partner = computed(() => (page.props as any).partner as User | undefined);
const wallets = computed(() => ((page.props as any).wallets || []) as Wallet[]);
const categories = computed(
    () => ((page.props as any).categories || []) as Category[],
);

const { isOpen: isDrawerOpen, defaults: transactionDefaults } =
    useTransactionModal();
const isNavigating = ref(false);

watch(
    () => (page.props as any).statusMessage,
    (statusMessage?: { type: 'success' | 'error'; message: string }) => {
        if (statusMessage?.message) {
            toast[statusMessage.type](statusMessage.message);
        }
    },
    { immediate: true, flush: 'post' },
);

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
    if (removeStartListener) {
        removeStartListener();
    }

    if (removeFinishListener) {
        removeFinishListener();
    }
});
</script>

<template>
    <div
        class="min-h-screen bg-zinc-50 pb-[calc(7rem+env(safe-area-inset-bottom))] text-zinc-900 antialiased selection:bg-rose-500 selection:text-white dark:bg-zinc-950 dark:text-zinc-100"
    >
        <!-- Top Loading Progress Bar (Glowing Gradient Line) -->
        <div
            v-if="isNavigating"
            class="fixed top-0 right-0 left-0 z-50 h-1 animate-pulse bg-gradient-to-r from-indigo-500 via-rose-500 to-amber-500 shadow-sm shadow-rose-500/50"
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
            :defaults="transactionDefaults"
            @created="isDrawerOpen = false"
        />

        <!-- Global Logout Confirmation Modal -->
        <LogoutConfirmModal />

        <PwaInstallPrompt />
        <Toaster position="top-center" rich-colors />
    </div>
</template>

<style>
/* Smooth SPA Page Transition */
.page-enter-active,
.page-leave-active {
    transition:
        opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1),
        transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
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
