<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { User, Shield, Palette } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

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
</script>

<template>
    <div class="space-y-6">
        <!-- Settings Top Header -->
        <div class="flex items-center justify-between border-b border-zinc-200/80 pb-4 dark:border-zinc-800/80">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Pengaturan Akun</h1>
                <p class="text-xs text-zinc-500">Kelola profil, keamanan, dan preferensi tampilan</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex gap-2 border-b border-zinc-200/80 pb-3 dark:border-zinc-800/80">
            <Link
                v-for="item in navItems"
                :key="item.title"
                :href="item.href"
                class="flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all"
                :class="[
                    isCurrentOrParentUrl(item.href)
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800/60 dark:text-zinc-400 dark:hover:bg-zinc-800'
                ]"
            >
                <component :is="item.icon" class="h-3.5 w-3.5" />
                {{ item.title }}
            </Link>
        </div>

        <!-- Content Area -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <slot />
        </div>
    </div>
</template>
