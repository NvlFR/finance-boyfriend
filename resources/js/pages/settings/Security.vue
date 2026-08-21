<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ShieldCheck, KeyRound, Lock, Sparkles, CheckCircle2, Fingerprint } from '@lucide/vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Label } from '@/components/ui/label';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();
</script>

<template>
    <Head title="Keamanan & Sandi - Couple Finance" />

    <div class="space-y-6">
        <!-- Security Header & Health Card -->
        <div class="rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-950 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600/80 text-white shadow-md ring-2 ring-indigo-400/20">
                        <ShieldCheck class="h-6 w-6 text-indigo-200" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold">Pusat Keamanan Akun</h2>
                        <p class="text-xs text-zinc-400">Data keuangan dan transaksi kamu terproteksi</p>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 px-3 py-1 text-[11px] font-bold text-emerald-300">
                    <CheckCircle2 class="h-3.5 w-3.5" /> Terlindungi
                </span>
            </div>
        </div>

        <!-- Section 1: Update Password -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
            <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-800">
                <Lock class="h-4 w-4 text-indigo-500" />
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        Perbarui Kata Sandi
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Gunakan kombinasi minimal 8 karakter dengan huruf, angka, dan simbol
                    </p>
                </div>
            </div>

            <Form
                v-bind="SecurityController.update.form()"
                :options="{
                    preserveScroll: true,
                }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-1.5">
                    <Label for="current_password" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                        Kata Sandi Saat Ini
                    </Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi lama"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="password" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                        Kata Sandi Baru
                    </Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter baru"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="password_confirmation" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                        Konfirmasi Kata Sandi Baru
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        autocomplete="new-password"
                        placeholder="Ulangi kata sandi baru"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="processing"
                        data-test="update-password-button"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-500 transition-all disabled:opacity-50"
                    >
                        <Sparkles class="h-4 w-4" />
                        <span>Simpan Kata Sandi Baru</span>
                    </button>
                </div>
            </Form>
        </div>

        <!-- Section 2: Two Factor Authentication -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <ManageTwoFactor
                :canManageTwoFactor="canManageTwoFactor"
                :requiresConfirmation="requiresConfirmation"
                :twoFactorEnabled="twoFactorEnabled"
            />
        </div>

        <!-- Section 3: Passkeys / Biometrics -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <ManagePasskeys
                :canManagePasskeys="canManagePasskeys"
                :passkeys="passkeys"
            />
        </div>
    </div>
</template>
