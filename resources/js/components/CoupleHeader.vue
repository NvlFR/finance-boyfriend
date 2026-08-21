<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Heart, Flame, Sparkles, User, Settings, LogOut, Shield, ChevronDown } from '@lucide/vue';
import { useLogoutModal } from '@/composables/useLogoutModal';
import type { CoupleSpace } from '@/types/finance';
import type { User as AuthUser } from '@/types/auth';

defineProps<{
    user: AuthUser;
    partner?: AuthUser | null;
    coupleSpace?: CoupleSpace | null;
}>();

const isMenuOpen = ref(false);
const { openLogoutModal } = useLogoutModal();

function logout() {
    isMenuOpen.value = false;
    openLogoutModal();
}
</script>

<template>
    <header class="w-full border-b border-zinc-200/80 bg-white/80 px-4 py-3 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
        <div class="mx-auto flex max-w-5xl items-center justify-between">
            <!-- Left: Couple Brand & Avatars -->
            <div class="flex items-center gap-3">
                <div class="flex items-center -space-x-2">
                    <!-- User Avatar -->
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-indigo-500 text-xs font-bold text-white shadow-sm ring-1 ring-indigo-500/20 dark:border-zinc-900"
                    >
                        {{ user.nickname?.charAt(0) || user.name.charAt(0) }}
                    </div>

                    <!-- Heart Connector -->
                    <div class="z-10 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-white shadow-sm">
                        <Heart class="h-3 w-3 fill-current" />
                    </div>

                    <!-- Partner Avatar -->
                    <div
                        v-if="partner"
                        class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-xs font-bold text-white shadow-sm ring-1 ring-rose-500/20 dark:border-zinc-900"
                    >
                        {{ partner.nickname?.charAt(0) || partner.name.charAt(0) }}
                    </div>
                    <div
                        v-else
                        class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-dashed border-zinc-300 bg-zinc-100 text-xs text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        ?
                    </div>
                </div>

                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ coupleSpace?.name || 'Couple Finance' }}
                        </span>
                    </div>
                    <span v-if="partner" class="text-[11px] text-zinc-500 dark:text-zinc-400">
                        Keuangan Berdua & Split Bill
                    </span>
                    <Link
                        v-else
                        href="/couple-space"
                        class="text-[11px] font-semibold text-rose-500 hover:underline flex items-center gap-1"
                    >
                        <Sparkles class="h-3 w-3" /> Hubungkan Pasangan
                    </Link>
                </div>
            </div>

            <!-- Right: Streak & Profile Dropdown -->
            <div class="flex items-center gap-2">
                <!-- Streak Badge -->
                <div
                    class="hidden sm:flex items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/20"
                >
                    <Flame class="h-3.5 w-3.5 fill-current text-amber-500" />
                    <span>7 Hari</span>
                </div>

                <!-- Profile Menu Trigger -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isMenuOpen = !isMenuOpen"
                        class="flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 py-1 pl-1.5 pr-2.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white">
                            {{ user.name.charAt(0) }}
                        </div>
                        <span class="max-w-[70px] truncate">{{ user.nickname || user.name.split(' ')[0] }}</span>
                        <ChevronDown class="h-3.5 w-3.5 text-zinc-400" />
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        v-if="isMenuOpen"
                        @click="isMenuOpen = false"
                        class="absolute right-0 top-full mt-2 w-48 rounded-2xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 z-50 animate-in fade-in zoom-in-95 duration-100"
                    >
                        <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ user.name }}</p>
                            <p class="text-[10px] text-zinc-500 truncate">{{ user.email }}</p>
                        </div>

                        <div class="py-1 space-y-0.5">
                            <Link
                                href="/settings/profile"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <User class="h-4 w-4 text-indigo-500" /> Edit Profil
                            </Link>
                            <Link
                                href="/settings/security"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <Shield class="h-4 w-4 text-amber-500" /> Password & Keamanan
                            </Link>
                            <Link
                                href="/couple-space"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <Heart class="h-4 w-4 text-rose-500" /> Ruang Pasangan
                            </Link>
                        </div>

                        <div class="border-t border-zinc-100 dark:border-zinc-800 pt-1">
                            <button
                                type="button"
                                @click="logout"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            >
                                <LogOut class="h-4 w-4" /> Keluar (Logout)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
