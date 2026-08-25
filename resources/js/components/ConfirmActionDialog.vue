<script setup lang="ts">
import { AlertTriangle, X } from '@lucide/vue';
import { toRef } from 'vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description: string;
        confirmLabel?: string;
        processing?: boolean;
    }>(),
    {
        confirmLabel: 'Ya, Hapus',
        processing: false,
    },
);

const emit = defineEmits<{
    (event: 'update:open', value: boolean): void;
    (event: 'confirm'): void;
}>();

function closeDialog(): void {
    if (!props.processing) {
        emit('update:open', false);
    }
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    toRef(props, 'open'),
    closeDialog,
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-sm"
            @click.self="closeDialog"
        >
            <div
                ref="dialogRef"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-action-title"
                aria-describedby="confirm-action-description"
                tabindex="-1"
                class="w-full max-w-sm rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
                @keydown="handleDialogKeydown"
            >
                <div class="flex items-start justify-between gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                    >
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <button
                        type="button"
                        :disabled="processing"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 disabled:opacity-50 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                        aria-label="Tutup dialog konfirmasi"
                        @click="closeDialog"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-1.5">
                    <h2
                        id="confirm-action-title"
                        class="text-base font-extrabold text-zinc-900 dark:text-zinc-100"
                    >
                        {{ title }}
                    </h2>
                    <p
                        id="confirm-action-description"
                        class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400"
                    >
                        {{ description }}
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        :disabled="processing"
                        class="min-h-11 rounded-2xl border border-zinc-200 px-4 py-2.5 text-xs font-bold text-zinc-700 transition-colors hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        @click="closeDialog"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="processing"
                        class="min-h-11 rounded-2xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-rose-700 disabled:opacity-50"
                        @click="emit('confirm')"
                    >
                        {{ processing ? 'Menghapus...' : confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
