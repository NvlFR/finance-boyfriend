<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { isPwaInstallable, isPwaInstalled, promptPwaInstall } from '@/lib/pwa';
import { Download, X, Smartphone, Sparkles, Share, PlusSquare } from '@lucide/vue';

const isDismissed = ref(false);
const isIos = ref(false);
const showIosGuide = ref(false);

onMounted(() => {
    // Check if dismissed in this session
    if (sessionStorage.getItem('pwa_prompt_dismissed') === 'true') {
        isDismissed.value = true;
    }

    // Detect iOS Safari
    const userAgent = window.navigator.userAgent.toLowerCase();
    isIos.value = /iphone|ipad|ipod/.test(userAgent) && !(window.navigator as any).standalone;
});

const shouldShow = computed(() => {
    if (isPwaInstalled.value || isDismissed.value) return false;
    return isPwaInstallable.value || (isIos.value && !isPwaInstalled.value);
});

async function handleInstall() {
    if (isIos.value && !isPwaInstallable.value) {
        showIosGuide.value = !showIosGuide.value;
        return;
    }

    const installed = await promptPwaInstall();
    if (installed) {
        isDismissed.value = true;
    }
}

function dismiss() {
    isDismissed.value = true;
    sessionStorage.setItem('pwa_prompt_dismissed', 'true');
}
</script>

<template>
    <div
        v-if="shouldShow"
        class="fixed bottom-20 left-4 right-4 z-40 mx-auto max-w-md animate-in fade-in slide-in-from-bottom-6 duration-300 sm:bottom-6 sm:right-6 sm:left-auto"
    >
        <div
            class="relative overflow-hidden rounded-3xl border border-indigo-500/30 bg-zinc-900/95 p-4 text-white shadow-2xl backdrop-blur-xl dark:border-indigo-500/20"
        >
            <!-- Glowing gradient ambient -->
            <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-rose-500/20 blur-xl pointer-events-none" />
            <div class="absolute -left-8 -bottom-8 h-28 w-28 rounded-full bg-indigo-500/20 blur-xl pointer-events-none" />

            <div class="relative z-10 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 to-rose-500 p-2 shadow-lg shadow-indigo-500/30">
                            <img src="/icons/icon-192.png" alt="Couple Finance" class="h-full w-full rounded-xl object-cover" />
                        </div>

                        <div>
                            <h4 class="text-xs font-extrabold tracking-tight text-white flex items-center gap-1.5">
                                <span>Install Couple Finance</span>
                                <Sparkles class="h-3 w-3 text-amber-400" />
                            </h4>
                            <p class="text-[11px] text-zinc-400">
                                Buka lebih cepat tanpa browser & offline-ready di layar utama HP!
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="dismiss"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-800 hover:text-white transition-colors"
                        title="Tutup"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- iOS Step Guide If on iOS Safari -->
                <div v-if="showIosGuide" class="rounded-2xl bg-zinc-800/80 p-3 text-[11px] space-y-1.5 text-zinc-300 border border-zinc-700">
                    <p class="font-bold text-white flex items-center gap-1">
                        <Smartphone class="h-3.5 w-3.5 text-rose-400" /> Cara Install di iPhone / Safari:
                    </p>
                    <p class="flex items-center gap-1.5">
                        1. Ketuk tombol <Share class="h-3.5 w-3.5 text-indigo-400 inline" /> (Share) di Safari
                    </p>
                    <p class="flex items-center gap-1.5">
                        2. Pilih <PlusSquare class="h-3.5 w-3.5 text-rose-400 inline" /> <strong>"Add to Home Screen"</strong>
                    </p>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-2 pt-0.5">
                    <button
                        type="button"
                        @click="handleInstall"
                        class="flex-1 flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-rose-500 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/25 transition-all hover:opacity-95 active:scale-[0.98]"
                    >
                        <Download class="h-3.5 w-3.5" />
                        <span>{{ isIos && !isPwaInstallable ? 'Lihat Cara Pasang di iOS' : 'Install ke HP Sekarang' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="dismiss"
                        class="rounded-xl border border-zinc-700 bg-zinc-800 px-3 py-2.5 text-xs font-medium text-zinc-400 hover:bg-zinc-700 hover:text-zinc-200 transition-colors"
                    >
                        Nanti
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
