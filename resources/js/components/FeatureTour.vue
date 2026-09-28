<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, X } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { useAccessibleDialog } from '@/composables/useAccessibleDialog';
import type { AppRelease } from '@/types/ui';

const props = defineProps<{
    release: AppRelease | null;
    userId: number;
    startRequest: number;
}>();

const page = usePage();
const isOpen = ref(false);
const currentIndex = ref(0);
const targetRect = ref<DOMRect | null>(null);
const isLocating = ref(false);
const viewport = ref({ width: 360, height: 640 });
const cardHeight = ref(260);
let locationSequence = 0;
let locationTimer: ReturnType<typeof setTimeout> | undefined;
let cardObserver: ResizeObserver | undefined;
const activeStep = computed(
    () => props.release?.tour[currentIndex.value] ?? null,
);
const totalSteps = computed(() => props.release?.tour.length ?? 0);
const isLastStep = computed(() => currentIndex.value === totalSteps.value - 1);
const isWaitingForTarget = computed(
    () => isLocating.value && targetRect.value === null,
);
const completionKey = computed(
    () =>
        `finance-couple:feature-tour:${props.userId}:${props.release?.version ?? 'unknown'}`,
);

const cardStyle = computed(() => {
    const rect = targetRect.value;

    const height = Math.min(cardHeight.value, viewport.value.height - 24);
    const width = Math.min(360, viewport.value.width - 24);
    const left = Math.min(
        Math.max(12, rect?.left ?? (viewport.value.width - width) / 2),
        viewport.value.width - width - 12,
    );
    const preferredTop = !rect
        ? (viewport.value.height - height) / 2
        : rect.bottom + height + 24 <= viewport.value.height
          ? rect.bottom + 12
          : rect.top - height - 12;

    return {
        top: `${Math.max(12, Math.min(preferredTop, viewport.value.height - height - 12))}px`,
        left: `${left}px`,
        width: `${width}px`,
        maxHeight: `${Math.max(100, viewport.value.height - 24)}px`,
    };
});

const spotlightStyle = computed(() => {
    const rect = targetRect.value;

    if (!rect) {
        return { display: 'none' };
    }

    return {
        top: `${Math.max(8, rect.top - 6)}px`,
        left: `${Math.max(8, rect.left - 6)}px`,
        width: `${Math.min(window.innerWidth - 16, rect.width + 12)}px`,
        height: `${rect.height + 12}px`,
    };
});

function currentPath(): string {
    return page.url.split('?')[0].replace(/\/$/, '') || '/';
}

async function locateTarget(): Promise<void> {
    const step = activeStep.value;
    const sequence = ++locationSequence;
    clearTimeout(locationTimer);

    if (!step) {
        return;
    }

    isLocating.value = true;
    await nextTick();
    locationTimer = setTimeout(() => {
        if (!isOpen.value || sequence !== locationSequence) {
            return;
        }

        const element = document.querySelector<HTMLElement>(step.target);
        element?.scrollIntoView({ behavior: 'instant', block: 'center' });
        targetRect.value = element?.getBoundingClientRect() ?? null;
        isLocating.value = false;
        void nextTick(() => {
            if (!isOpen.value || sequence !== locationSequence) {
                return;
            }

            cardHeight.value = dialogRef.value?.offsetHeight ?? 260;
            dialogRef.value?.focus();
        });
    }, 0);
}

function showCurrentStep(): void {
    targetRect.value = null;
    isLocating.value = true;
    const step = activeStep.value;

    if (!step) {
        finishTour();

        return;
    }

    if (currentPath() !== step.path) {
        targetRect.value = null;
        isLocating.value = true;
        router.visit(step.path, {
            replace: true,
            preserveState: false,
            preserveScroll: false,
            onFinish: locateTarget,
        });

        return;
    }

    void locateTarget();
}

function startTour(): void {
    if (!props.release?.tour.length) {
        return;
    }

    currentIndex.value = 0;
    isOpen.value = true;
    showCurrentStep();
}

function nextStep(): void {
    if (isLocating.value) {
        return;
    }

    if (isLastStep.value) {
        finishTour();

        return;
    }

    currentIndex.value += 1;
    showCurrentStep();
}

function previousStep(): void {
    if (isLocating.value) {
        return;
    }

    if (currentIndex.value === 0) {
        return;
    }

    currentIndex.value -= 1;
    showCurrentStep();
}

function finishTour(): void {
    locationSequence += 1;
    clearTimeout(locationTimer);

    if (typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(completionKey.value, 'completed');
        } catch {
            // The tour can still close when browser storage is unavailable.
        }
    }

    isOpen.value = false;
    targetRect.value = null;
}

const { dialogRef, handleDialogKeydown } = useAccessibleDialog(
    isOpen,
    finishTour,
);

function refreshTarget(): void {
    viewport.value = { width: window.innerWidth, height: window.innerHeight };

    if (isOpen.value) {
        const element = activeStep.value
            ? document.querySelector<HTMLElement>(activeStep.value.target)
            : null;
        targetRect.value = element?.getBoundingClientRect() ?? null;
    }
}

function handleKeydown(event: KeyboardEvent): void {
    if (!isOpen.value) {
        return;
    }

    if (event.key === 'ArrowRight') {
        nextStep();
    } else if (event.key === 'ArrowLeft') {
        previousStep();
    }
}

function handleStartTourEvent(): void {
    startTour();
}

watch(() => props.startRequest, startTour);

onMounted(() => {
    refreshTarget();
    cardObserver = new ResizeObserver(() => {
        cardHeight.value = dialogRef.value?.offsetHeight ?? 260;
    });
    window.addEventListener('resize', refreshTarget);
    window.addEventListener('scroll', refreshTarget, true);
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('finance:start-feature-tour', handleStartTourEvent);
});

onBeforeUnmount(() => {
    locationSequence += 1;
    clearTimeout(locationTimer);
    cardObserver?.disconnect();
    window.removeEventListener('resize', refreshTarget);
    window.removeEventListener('scroll', refreshTarget, true);
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener(
        'finance:start-feature-tour',
        handleStartTourEvent,
    );
});

watch(dialogRef, (element) => {
    cardObserver?.disconnect();

    if (element) {
        cardObserver?.observe(element);
    }
});
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen && activeStep" class="fixed inset-0 z-[90]">
            <div
                v-if="!targetRect"
                class="absolute inset-0 bg-slate-950/75 backdrop-blur-[1px]"
            />
            <div
                class="pointer-events-none fixed rounded-3xl shadow-[0_0_0_9999px_rgba(2,6,23,0.78)] ring-4 ring-white transition-all duration-300"
                :style="spotlightStyle"
            />

            <section
                ref="dialogRef"
                role="dialog"
                aria-modal="true"
                aria-labelledby="feature-tour-title"
                tabindex="-1"
                class="fixed overflow-y-auto rounded-[1.5rem] border border-white/20 bg-white p-5 shadow-2xl transition-opacity duration-150 outline-none dark:border-zinc-700 dark:bg-zinc-900"
                :class="{ invisible: isWaitingForTarget }"
                :style="cardStyle"
                :aria-busy="isWaitingForTarget"
                @keydown="handleDialogKeydown"
            >
                <div class="flex items-center justify-between gap-3">
                    <span
                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                    >
                        {{ currentIndex + 1 }} dari {{ totalSteps }}
                    </span>
                    <button
                        type="button"
                        class="grid h-10 w-10 place-items-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Tutup tur"
                        @click="finishTour"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <h2
                    id="feature-tour-title"
                    class="mt-3 text-lg font-black text-zinc-950 dark:text-white"
                >
                    {{ activeStep.title }}
                </h2>
                <p
                    class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300"
                >
                    {{ activeStep.description }}
                </p>
                <p
                    v-if="isLocating"
                    class="mt-2 text-xs text-indigo-600 dark:text-indigo-400"
                    aria-live="polite"
                >
                    Membuka fiturnya…
                </p>

                <div class="mt-5 flex items-center justify-between gap-2">
                    <button
                        type="button"
                        :disabled="currentIndex === 0 || isLocating"
                        class="inline-flex min-h-11 items-center gap-1 rounded-xl px-3 text-sm font-bold text-zinc-500 disabled:invisible"
                        @click="previousStep"
                    >
                        <ArrowLeft class="h-4 w-4" /> Kembali
                    </button>
                    <button
                        type="button"
                        :disabled="isLocating"
                        class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-500/20"
                        @click="nextStep"
                    >
                        {{ isLastStep ? 'Selesai' : 'Lanjut' }}
                        <Check v-if="isLastStep" class="h-4 w-4" />
                        <ArrowRight v-else class="h-4 w-4" />
                    </button>
                </div>
            </section>
        </div>
    </Teleport>
</template>
