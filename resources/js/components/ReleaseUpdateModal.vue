<script setup lang="ts">
import { CheckCircle2, PartyPopper, Sparkles, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import type { AppRelease } from '@/types/ui';

const props = defineProps<{
    release: AppRelease | null;
    userId: number;
}>();

const isOpen = ref(false);
const storageKey = computed(
    () => `finance-couple:last-seen-release:${props.userId}`,
);

function hasSeenCurrentRelease(): boolean {
    if (!props.release || typeof window === 'undefined') {
        return true;
    }

    try {
        return (
            window.localStorage.getItem(storageKey.value) ===
            props.release.version
        );
    } catch {
        return false;
    }
}

function dismissRelease(): void {
    if (props.release && typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(
                storageKey.value,
                props.release.version,
            );
        } catch {
            // The announcement still closes when browser storage is unavailable.
        }
    }

    isOpen.value = false;
}

function formatReleaseDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    isOpen,
    dismissRelease,
);

watch(
    () => props.release?.version,
    (version) => {
        if (!version || typeof window === 'undefined') {
            return;
        }

        isOpen.value = !hasSeenCurrentRelease();
    },
    { immediate: true },
);
</script>

<template>
    <div
        v-if="isOpen && release"
        class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/65 p-3 backdrop-blur-sm sm:items-center"
        @click.self="dismissRelease"
    >
        <section
            ref="dialogRef"
            role="dialog"
            aria-modal="true"
            aria-labelledby="release-update-title"
            tabindex="-1"
            class="relative max-h-[calc(100dvh-1.5rem)] w-full max-w-md overflow-y-auto rounded-[2rem] border border-white/20 bg-white shadow-2xl outline-none dark:border-zinc-700 dark:bg-zinc-900"
            @keydown="handleDialogKeydown"
        >
            <div
                class="relative overflow-hidden rounded-t-[2rem] bg-gradient-to-br from-indigo-700 via-violet-600 to-rose-500 px-5 pt-5 pb-7 text-white"
            >
                <div
                    class="pointer-events-none absolute -top-10 -right-6 h-32 w-32 rounded-full bg-white/15 blur-2xl"
                />
                <button
                    type="button"
                    class="absolute top-3 right-3 flex h-11 w-11 items-center justify-center rounded-full bg-black/15 text-white transition-colors hover:bg-black/25"
                    aria-label="Tutup informasi pembaruan"
                    @click="dismissRelease"
                >
                    <X class="h-5 w-5" />
                </button>

                <div class="relative pr-12">
                    <div
                        class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 shadow-lg ring-1 ring-white/30"
                    >
                        <PartyPopper class="h-6 w-6" />
                    </div>
                    <div
                        class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold tracking-wide uppercase"
                    >
                        <Sparkles class="h-3 w-3" />
                        Update v{{ release.version }}
                    </div>
                    <h2
                        id="release-update-title"
                        class="text-xl font-extrabold tracking-tight"
                    >
                        {{ release.title }}
                    </h2>
                    <p class="mt-1 text-xs text-white/75">
                        Dirilis {{ formatReleaseDate(release.released_at) }}
                    </p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <div>
                    <p
                        class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                    >
                        Yang baru di versi ini
                    </p>
                    <ul class="mt-3 space-y-3">
                        <li
                            v-for="highlight in release.highlights"
                            :key="highlight"
                            class="flex items-start gap-2.5 text-xs leading-relaxed text-zinc-600 dark:text-zinc-300"
                        >
                            <CheckCircle2
                                class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"
                            />
                            <span>{{ highlight }}</span>
                        </li>
                    </ul>
                </div>

                <button
                    type="button"
                    class="flex min-h-12 w-full items-center justify-center rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 transition-all hover:opacity-95 active:scale-[0.98]"
                    @click="dismissRelease"
                >
                    Oke, lihat pembaruannya
                </button>
            </div>
        </section>
    </div>
</template>
