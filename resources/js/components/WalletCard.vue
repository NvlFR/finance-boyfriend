<script setup lang="ts">
import {
    CreditCard,
    Wallet as WalletIcon,
    Smartphone,
    Landmark,
    Coins,
    Edit2,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Wallet } from '@/types/finance';

const props = defineProps<{
    wallet: Wallet;
    isJoint?: boolean;
    canManage?: boolean;
}>();

const emit = defineEmits<{
    (e: 'edit', wallet: Wallet): void;
    (e: 'delete', wallet: Wallet): void;
}>();

const formattedBalance = computed(() => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: props.wallet.currency || 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(props.wallet.balance));
});

const walletIconComponent = computed(() => {
    switch (props.wallet.wallet_type) {
        case 'bank':
            return Landmark;
        case 'ewallet':
            return Smartphone;
        case 'credit_card':
            return CreditCard;
        case 'cash':
            return Coins;
        default:
            return WalletIcon;
    }
});
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-2xl border p-4 shadow-sm transition-all hover:shadow-md"
        :class="[
            isJoint
                ? 'border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent dark:border-emerald-500/20'
                : 'border-zinc-200/80 bg-white dark:border-zinc-800/80 dark:bg-zinc-900',
        ]"
    >
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-sm"
                    :style="{ backgroundColor: wallet.color || '#6366F1' }"
                >
                    <component :is="walletIconComponent" class="h-5 w-5" />
                </div>
                <div>
                    <h3
                        class="text-sm font-semibold text-zinc-900 dark:text-zinc-100"
                    >
                        {{ wallet.name }}
                    </h3>
                    <p
                        class="text-xs text-zinc-500 capitalize dark:text-zinc-400"
                    >
                        {{ wallet.wallet_type }}
                        {{
                            wallet.account_number
                                ? `• ${wallet.account_number}`
                                : ''
                        }}
                    </p>
                    <p
                        class="mt-0.5 text-[11px] font-semibold"
                        :class="
                            isJoint
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-indigo-600 dark:text-indigo-400'
                        "
                    >
                        {{
                            isJoint
                                ? 'Milik Bersama'
                                : `Milik ${wallet.user?.nickname || wallet.user?.name || 'Pemilik tidak diketahui'}`
                        }}
                    </p>
                </div>
            </div>

            <!-- Badges and Actions -->
            <div class="flex items-center gap-1.5">
                <span
                    v-if="isJoint"
                    class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                >
                    Kas Bersama
                </span>
                <!-- Quick Action Buttons -->
                <button
                    v-if="canManage"
                    type="button"
                    @click.stop="emit('edit', wallet)"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                    title="Edit Dompet"
                >
                    <Edit2 class="h-3.5 w-3.5" />
                </button>

                <button
                    v-if="canManage"
                    type="button"
                    @click.stop="emit('delete', wallet)"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                    title="Hapus Dompet"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <div class="mt-4 flex items-baseline justify-between">
            <span
                class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100"
            >
                {{ formattedBalance }}
            </span>
        </div>
    </div>
</template>
