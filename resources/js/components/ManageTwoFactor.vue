<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck, ShieldAlert, KeyRound, Sparkles } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                    <ShieldCheck class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        Autentikasi Dua Langkah (2FA)
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Lindungi akun dengan verifikasi kode OTP dari aplikasi authenticator
                    </p>
                </div>
            </div>

            <!-- Status Badge -->
            <span
                v-if="twoFactorEnabled"
                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-bold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
            >
                Aktif
            </span>
            <span
                v-else
                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
            >
                Nonaktif
            </span>
        </div>

        <!-- Inactive State -->
        <div
            v-if="!twoFactorEnabled"
            class="space-y-3"
        >
            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                Saat 2FA diaktifkan, kamu akan diminta memasukkan 6 digit kode keamanan dari aplikasi seperti Google Authenticator atau Authy setiap kali login di perangkat baru.
            </p>

            <div>
                <button
                    v-if="hasSetupData"
                    type="button"
                    @click="showSetupModal = true"
                    class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                >
                    <ShieldCheck class="h-4 w-4" />
                    <span>Lanjutkan Konfigurasi 2FA</span>
                </button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <button
                        type="submit"
                        :disabled="processing"
                        class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all disabled:opacity-50"
                    >
                        <Sparkles class="h-4 w-4" />
                        <span>Aktifkan 2FA Sekarang</span>
                    </button>
                </Form>
            </div>
        </div>

        <!-- Active State -->
        <div v-else class="space-y-4">
            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                2FA aktif di akunmu. Simpan kode pemulihan darurat di tempat yang aman jika suatu saat kehilangan akses ke ponselmu.
            </p>

            <div class="relative inline-block">
                <Form v-bind="disable.form()" #default="{ processing }">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="inline-flex items-center gap-1.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-400 dark:hover:bg-rose-950/50 transition-colors disabled:opacity-50"
                    >
                        <ShieldAlert class="h-4 w-4" />
                        <span>Nonaktifkan 2FA</span>
                    </button>
                </Form>
            </div>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
