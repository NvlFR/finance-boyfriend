<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Sparkles, Mail, Lock, LogIn, Heart } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk ke Akun',
        description: 'Kelola kas, tabungan, dan impian bersama pasanganmu',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Masuk - Couple Finance" />

    <div
        v-if="status"
        class="mb-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-3 text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="space-y-4"
    >
        <!-- Email Input -->
        <div class="space-y-1">
            <label for="email" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                Alamat Email
            </label>
            <div class="relative">
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    tabindex="1"
                    autocomplete="email"
                    placeholder="nama@example.com"
                    class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <!-- Password Input -->
        <div class="space-y-1">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    Kata Sandi
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="request()"
                    tabindex="5"
                    class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                >
                    Lupa sandi?
                </Link>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                tabindex="2"
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
            />
            <InputError :message="errors.password" />
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center pt-1">
            <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    tabindex="3"
                    class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button
                type="submit"
                tabindex="4"
                :disabled="processing"
                data-test="login-button"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/25 hover:opacity-95 transition-all disabled:opacity-50"
            >
                <Spinner v-if="processing" />
                <LogIn v-else class="h-4 w-4" />
                <span>Masuk Sekarang</span>
            </button>
        </div>

        <!-- OR Divider -->
        <div class="relative flex items-center justify-center my-3">
            <div class="border-t border-zinc-200 dark:border-zinc-800 w-full" />
            <span class="bg-white dark:bg-zinc-900 px-3 text-[10px] font-bold text-zinc-400 uppercase tracking-wider absolute">atau</span>
        </div>

        <!-- Google OAuth Button -->
        <a
            href="/auth/google"
            class="flex w-full items-center justify-center gap-2.5 rounded-2xl border border-zinc-200 bg-white py-2.5 text-xs font-bold text-zinc-700 shadow-xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
            </svg>
            <span>Masuk dengan Google</span>
        </a>

        <!-- Register Link -->
        <div class="pt-2 text-center text-xs text-zinc-500">
            Belum punya akun bersama?
            <Link
                :href="register()"
                tabindex="6"
                class="font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 underline ml-1"
            >
                Daftar Gratis
            </Link>
        </div>
    </Form>
</template>
