<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Mail, ArrowLeft, Sparkles } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa Kata Sandi',
        description: 'Masukkan alamat email kamu untuk menerima tautan reset kata sandi',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa Kata Sandi - Couple Finance" />

    <div
        v-if="status"
        class="mb-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-3 text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400"
    >
        {{ status }}
    </div>

    <div class="space-y-4">
        <Form v-bind="email.form()" v-slot="{ errors, processing }" class="space-y-4">
            <div class="space-y-1">
                <label for="email" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    Alamat Email
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    required
                    placeholder="nama@example.com"
                    class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/25 hover:opacity-95 transition-all disabled:opacity-50"
                >
                    <Spinner v-if="processing" />
                    <Mail v-else class="h-4 w-4" />
                    <span>Kirim Tautan Reset Sandi</span>
                </button>
            </div>
        </Form>

        <div class="pt-2 text-center text-xs text-zinc-500">
            Ingat kata sandi akunmu?
            <Link :href="login()" class="font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 underline ml-1">
                Kembali ke Login
            </Link>
        </div>
    </div>
</template>
