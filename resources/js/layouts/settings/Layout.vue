<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { User, Shield, Palette } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { AppRelease } from '@/types/ui';

const navItems = [
    {
        title: 'Profil',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Keamanan',
        href: editSecurity(),
        icon: Shield,
    },
    {
        title: 'Tampilan',
        href: editAppearance(),
        icon: Palette,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();
const appRelease = computed(
    () => ((page.props as any).appRelease || null) as AppRelease | null,
);

function replayFeatureTour(): void {
    window.dispatchEvent(new CustomEvent('finance:start-feature-tour'));
}
</script>

<template>
    <div class="space-y-4 sm:space-y-5">
        <PageHeader title="Pengaturan Akun" />

        <!-- Navigation Tabs -->
        <nav
            class="grid grid-cols-3 gap-1 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            aria-label="Navigasi pengaturan akun"
        >
            <Link
                v-for="item in navItems"
                :key="item.title"
                :href="item.href"
                class="flex min-h-11 items-center justify-center gap-1.5 rounded-xl px-2 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                :class="[
                    isCurrentOrParentUrl(item.href)
                        ? 'bg-indigo-900 text-white shadow-sm dark:bg-indigo-500'
                        : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                ]"
            >
                <component :is="item.icon" class="h-3.5 w-3.5" />
                {{ item.title }}
            </Link>
        </nav>

        <!-- Content Area -->
        <div
            class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
            <slot />
        </div>

        <div
            v-if="appRelease"
            class="flex flex-wrap items-center justify-center gap-2 text-center text-[11px] font-medium text-zinc-400 dark:text-zinc-500"
        >
            <span>
                Couple Finance v{{ appRelease.version }} · Diperbarui
                {{
                    new Date(
                        `${appRelease.released_at}T00:00:00`,
                    ).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                    })
                }}
            </span>
            <button
                v-if="appRelease.tour.length"
                type="button"
                class="min-h-10 rounded-full px-3 font-bold text-indigo-600 transition-colors hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40"
                @click="replayFeatureTour"
            >
                Lihat tur fitur
            </button>
        </div>
    </div>
</template>
