<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { UserPlus, Sparkles, Heart } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Buat Akun Baru',
        description: 'Mulai perjalanan finansial yang terbuka dan harmonis bersama pasangan',
    },
});
</script>

<template>
    <Head title="Daftar Akun - Couple Finance" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="space-y-4"
    >
        <!-- Name Input -->
        <div class="space-y-1">
            <label for="name" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                Nama Lengkap
            </label>
            <input
                id="name"
                type="text"
                required
                autofocus
                tabindex="1"
                autocomplete="name"
                name="name"
                placeholder="Contoh: Rony Pratama"
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
            />
            <InputError :message="errors.name" />
        </div>

        <!-- Email Input -->
        <div class="space-y-1">
            <label for="email" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                Alamat Email
            </label>
            <input
                id="email"
                type="email"
                required
                tabindex="2"
                autocomplete="email"
                name="email"
                placeholder="nama@example.com"
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
            />
            <InputError :message="errors.email" />
        </div>

        <!-- Password Input -->
        <div class="space-y-1">
            <label for="password" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                Kata Sandi
            </label>
            <PasswordInput
                id="password"
                required
                tabindex="3"
                autocomplete="new-password"
                name="password"
                placeholder="Minimal 8 karakter"
                :passwordrules="passwordRules"
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
            />
            <InputError :message="errors.password" />
        </div>

        <!-- Password Confirmation Input -->
        <div class="space-y-1">
            <label for="password_confirmation" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                Konfirmasi Kata Sandi
            </label>
            <PasswordInput
                id="password_confirmation"
                required
                tabindex="4"
                autocomplete="new-password"
                name="password_confirmation"
                placeholder="Ulangi kata sandi"
                :passwordrules="passwordRules"
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button
                type="submit"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/25 hover:opacity-95 transition-all disabled:opacity-50"
            >
                <Spinner v-if="processing" />
                <UserPlus v-else class="h-4 w-4" />
                <span>Daftar Akun Sekarang</span>
            </button>
        </div>

        <!-- Login Link -->
        <div class="pt-2 text-center text-xs text-zinc-500">
            Sudah memiliki akun?
            <Link
                :href="login()"
                tabindex="6"
                class="font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 underline ml-1"
            >
                Masuk di Sini
            </Link>
        </div>
    </Form>
</template>
