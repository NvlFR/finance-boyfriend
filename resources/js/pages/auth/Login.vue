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
