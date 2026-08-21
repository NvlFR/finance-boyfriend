<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { KeyRound, Fingerprint, Plus } from '@lucide/vue';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <div v-if="canManagePasskeys" class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                    <Fingerprint class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        Kunci Sandi (Passkeys & Biometrik)
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Masuk lebih instan dan aman menggunakan Fingerprint, Face ID, atau PIN perangkat
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-900/50">
            <template v-if="passkeys.length">
                <PasskeyItem
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    :passkey="passkey"
                    @remove="handleDelete"
                />
            </template>

            <div v-else class="p-6 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <KeyRound class="h-6 w-6 text-zinc-400" />
                </div>
                <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Belum Ada Passkey Terdaftar</p>
                <p class="mt-0.5 text-[11px] text-zinc-400">
                    Tambahkan Passkey agar dapat login otomatis tanpa perlu mengetik kata sandi
                </p>
            </div>
        </div>

        <div>
            <PasskeyRegister @success="handleRegisterSuccess" />
        </div>
    </div>
</template>
