<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Sun, Moon, Monitor, Sparkles, Check, Heart, Shield, Palette, Eye, ArrowUpRight } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import type { User } from '@/types/auth';

const page = usePage();
const user = computed(() => page.props.auth.user as User);
const { appearance, updateAppearance } = useAppearance();

const previewAccent = ref(user.value?.theme_color || '#6366F1');

const themeModes = [
    {
        value: 'light',
        label: 'Mode Terang (Light)',
        desc: 'Tampilan cerah, bersih, dan kontras tinggi di siang hari.',
        icon: Sun,
        color: 'text-amber-500',
    },
    {
        value: 'dark',
        label: 'Mode Gelap (Dark)',
        desc: 'Nyaman di mata saat malam dan menghemat daya baterai.',
        icon: Moon,
        color: 'text-indigo-400',
    },
    {
        value: 'system',
        label: 'Otomatis (Sistem)',
        desc: 'Menyesuaikan otomatis dengan pengaturan perangkatmu.',
        icon: Monitor,
        color: 'text-zinc-400',
    },
] as const;

const accentColors = [
    { label: 'Indigo Love', value: '#6366F1' },
    { label: 'Rose Sweet', value: '#F43F5E' },
    { label: 'Pink Candy', value: '#EC4899' },
    { label: 'Emerald Trust', value: '#10B981' },
    { label: 'Amber Warmth', value: '#F59E0B' },
    { label: 'Sky Harmony', value: '#3B82F6' },
    { label: 'Violet Dream', value: '#8B5CF6' },
    { label: 'Teal Fresh', value: '#14B8A6' },
];
</script>

<template>
    <Head title="Tampilan & Tema Aplikasi" />

    <div class="space-y-6">
        <!-- Top Title Header -->
        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
            <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                Tampilan & Tema Aplikasi
            </h2>
            <p class="text-xs text-zinc-500">
                Kustomisasi tema warna dan mode gelap untuk kenyamanan berdua
            </p>
        </div>

        <!-- Section 1: Mode Tampilan Interaktif -->
        <div class="space-y-3">
            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400">
                Mode Tampilan
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button
                    v-for="mode in themeModes"
                    :key="mode.value"
                    type="button"
                    @click="updateAppearance(mode.value)"
                    class="relative flex flex-col items-start p-4 rounded-3xl border text-left transition-all group"
                    :class="[
                        appearance === mode.value
                            ? 'border-indigo-500 bg-indigo-50/50 shadow-md ring-2 ring-indigo-500/20 dark:border-indigo-500 dark:bg-indigo-950/20'
                            : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-zinc-700',
                    ]"
                >
                    <div class="flex w-full items-center justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl border shadow-sm transition-transform group-hover:scale-105"
                            :class="appearance === mode.value ? 'bg-indigo-600 text-white border-transparent' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700'"
                        >
                            <component :is="mode.icon" class="h-5 w-5" :class="appearance === mode.value ? 'text-white' : mode.color" />
                        </div>

                        <span
                            v-if="appearance === mode.value"
                            class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white shadow-sm"
                        >
                            <Check class="h-3.5 w-3.5 stroke-[3]" />
                        </span>
                    </div>

                    <h3 class="mt-3 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ mode.label }}
                    </h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                        {{ mode.desc }}
                    </p>
                </button>
            </div>
        </div>

        <!-- Section 2: Live Simulator Widget -->
        <div class="space-y-3">
            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400">
                Simulasi Live Preview Tampilan
            </label>

            <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-900/40">
                <div class="mb-3 flex items-center justify-between text-xs text-zinc-500">
                    <span class="flex items-center gap-1.5 font-medium">
                        <Eye class="h-3.5 w-3.5 text-indigo-500" /> Preview Realtime di Mode {{ appearance === 'dark' ? 'Gelap' : (appearance === 'light' ? 'Terang' : 'Sistem') }}
                    </span>
                    <span class="text-[11px] text-zinc-400">Responsif langsung</span>
                </div>

                <!-- Mini Mockup Card -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white text-xs font-bold shadow-sm"
                                :style="{ backgroundColor: previewAccent }"
                            >
                                {{ user?.nickname?.charAt(0) || user?.name?.charAt(0) || 'R' }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Total Kas Pasangan</p>
                                <p class="text-[10px] text-zinc-400">Updated baru saja</p>
                            </div>
                        </div>

                        <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            +12% Bulan Ini
                        </span>
                    </div>

                    <div class="text-xl font-extrabold text-zinc-900 dark:text-zinc-100">
                        Rp 28.500.000
                    </div>

                    <!-- Mini Buttons -->
                    <div class="flex gap-2 pt-1">
                        <div
                            class="flex-1 rounded-xl py-2 text-center text-[11px] font-bold text-white shadow-sm"
                            :style="{ backgroundColor: previewAccent }"
                        >
                            + Catat Transaksi
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-[11px] font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            Lihat Tabungan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Palet Warna Aksen -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
            <div class="flex items-center gap-2">
                <Palette class="h-4 w-4 text-indigo-500" />
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                    Warna Aksen Kencan
                </h3>
            </div>
            <p class="text-xs text-zinc-500">
                Pilih warna favoritmu untuk mensimulasikan kartu kencan dan profil:
            </p>

            <div class="flex flex-wrap gap-3 pt-1">
                <button
                    v-for="color in accentColors"
                    :key="color.value"
                    type="button"
                    @click="previewAccent = color.value"
                    class="group relative flex items-center gap-2 rounded-2xl border px-3 py-2 text-xs font-semibold transition-all"
                    :class="[
                        previewAccent === color.value
                            ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/30'
                            : 'border-zinc-200 bg-zinc-50/50 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800/40',
                    ]"
                >
                    <span
                        class="h-4 w-4 rounded-full shadow-sm"
                        :style="{ backgroundColor: color.value }"
                    />
                    <span class="text-zinc-800 dark:text-zinc-200">{{ color.label }}</span>
                    <Check v-if="previewAccent === color.value" class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                </button>
            </div>
        </div>
    </div>
</template>
