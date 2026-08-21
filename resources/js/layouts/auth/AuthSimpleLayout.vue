<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Heart, Sparkles, ShieldCheck } from '@lucide/vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden bg-zinc-50 px-4 py-12 dark:bg-zinc-950">
        <!-- Ambient Glowing Background Bubbles -->
        <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-rose-500/15 blur-3xl" />
        <div class="absolute -bottom-24 -right-24 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl" />
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-80 w-80 rounded-full bg-purple-500/10 blur-3xl pointer-events-none" />

        <div class="relative z-10 w-full max-w-md">
            <!-- Brand Logo Header -->
            <div class="mb-6 flex flex-col items-center text-center">
                <Link
                    :href="home()"
                    class="group mb-3 flex items-center gap-2.5 rounded-full bg-white/80 px-4 py-2 shadow-sm border border-zinc-200/80 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/80 transition-transform group-hover:scale-105"
                >
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-tr from-rose-500 to-indigo-600 text-white shadow-md">
                        <Heart class="h-4 w-4 fill-current animate-pulse" />
                    </div>
                    <span class="text-sm font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Couple<span class="text-rose-500">Finance</span>
                    </span>
                </Link>

                <h1 class="text-xl sm:text-2xl font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight">
                    {{ title || 'Selamat Datang' }}
                </h1>
                <p v-if="description" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 max-w-xs">
                    {{ description }}
                </p>
            </div>

            <!-- Card Container -->
            <div class="rounded-3xl border border-zinc-200/80 bg-white/90 p-6 sm:p-8 shadow-2xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-900/90">
                <slot />
            </div>

            <!-- Security Badge Footer -->
            <div class="mt-6 flex items-center justify-center gap-2 text-[11px] text-zinc-400">
                <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
                <span>Data keuangan terenkripsi & aman berdua</span>
            </div>
        </div>
    </div>
</template>
