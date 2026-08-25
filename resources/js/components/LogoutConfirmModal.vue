<script setup lang="ts">
import { LogOut, X, ShieldCheck } from '@lucide/vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import { useLogoutModal } from '@/composables/useLogoutModal';

const { isLogoutModalOpen, isLoggingOut, closeLogoutModal, confirmLogout } =
    useLogoutModal();
const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    isLogoutModalOpen,
    closeLogoutModal,
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isLogoutModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md"
                @click.self="closeLogoutModal"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translateY(8px)"
                    enter-to-class="opacity-100 scale-100 translateY(0)"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translateY(0)"
                    leave-to-class="opacity-0 scale-95 translateY(8px)"
                >
                    <div
                        v-if="isLogoutModalOpen"
                        ref="dialogRef"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="logout-dialog-title"
                        tabindex="-1"
                        @keydown="handleDialogKeydown"
                        class="relative w-full max-w-sm overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
                        @click.stop
                    >
                        <!-- Ambient Glow -->
                        <div
                            class="pointer-events-none absolute -top-8 -right-8 h-24 w-24 rounded-full bg-rose-500/15 blur-xl"
                        />

                        <!-- Header & Icon -->
                        <div class="flex items-start justify-between">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-rose-500/20 bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                            >
                                <LogOut class="h-6 w-6" />
                            </div>
                            <button
                                type="button"
                                @click="closeLogoutModal"
                                class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                aria-label="Tutup dialog konfirmasi keluar"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Modal Body Text -->
                        <div class="mt-4 space-y-1.5">
                            <h3
                                id="logout-dialog-title"
                                class="text-lg font-extrabold text-zinc-900 dark:text-zinc-100"
                            >
                                Konfirmasi Keluar Akun
                            </h3>
                            <p
                                class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400"
                            >
                                Apakah kamu yakin ingin keluar dari akun
                                <strong>Couple Finance</strong>? Seluruh data
                                tabungan & transaksi kamu dan pasangan tetap
                                tersimpan dengan aman.
                            </p>
                        </div>

                        <div
                            class="mt-3 flex items-center gap-1.5 text-[11px] text-zinc-400 dark:text-zinc-500"
                        >
                            <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
                            <span>Sesi akan diakhiri secara aman</span>
                        </div>

                        <!-- Modal Actions -->
                        <div class="mt-6 flex items-center gap-2">
                            <button
                                type="button"
                                @click="closeLogoutModal"
                                class="flex-1 rounded-2xl border border-zinc-200 bg-white py-2.5 text-xs font-bold text-zinc-700 shadow-xs transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="confirmLogout"
                                :disabled="isLoggingOut"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-2xl bg-rose-600 py-2.5 text-xs font-bold text-white shadow-md shadow-rose-600/25 transition-all hover:bg-rose-700 active:scale-[0.98] disabled:opacity-50"
                            >
                                <LogOut class="h-3.5 w-3.5" />
                                <span>{{
                                    isLoggingOut
                                        ? 'Mengeluarkan...'
                                        : 'Ya, Keluar'
                                }}</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
