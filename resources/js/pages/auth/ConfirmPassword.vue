<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, ShieldAlert, KeyRound } from '@lucide/vue';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <Head title="Konfirmasi Kata Sandi - Couple Finance" />

    <div class="mx-auto max-w-md space-y-6 pt-4">
        <!-- Security Hero Card -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                <Lock class="h-7 w-7" />
            </div>

            <div>
                <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Konfirmasi Kata Sandi
                </h2>
                <p class="mt-1 text-xs text-zinc-500 leading-relaxed">
                    Ini adalah area keamanan khusus akunmu. Mohon masukkan kata sandi untuk melanjutkan.
                </p>
            </div>

            <!-- Passkey Fast Confirm -->
            <PasskeyVerify
                :routes="{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }"
                label="Konfirmasi dengan Passkey / Biometrik"
                loading-label="Mengonfirmasi..."
                separator="Atau masukkan kata sandi manual"
            />

            <!-- Form Confirm Password -->
            <Form
                v-bind="store.form()"
                reset-on-success
                v-slot="{ errors, processing }"
                class="space-y-4 text-left pt-2"
            >
                <div class="space-y-1">
                    <label for="password" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Kata Sandi Akun
                    </label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        autofocus
                        placeholder="Masukkan kata sandi kamu"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="pt-1">
                    <button
                        type="submit"
                        :disabled="processing"
                        data-test="confirm-password-button"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3 text-xs font-bold text-white shadow-md shadow-indigo-500/25 hover:opacity-95 transition-all disabled:opacity-50"
                    >
                        <Spinner v-if="processing" />
                        <Lock v-else class="h-4 w-4" />
                        <span>Konfirmasi & Lanjutkan</span>
                    </button>
                </div>
            </Form>
        </div>
    </div>
</template>
