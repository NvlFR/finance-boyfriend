<script setup lang="ts">
import {
    CakeSlice,
    ChevronLeft,
    ChevronRight,
    Gift,
    Heart,
    Images,
    MailOpen,
    Sparkles,
    TicketCheck,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import type { Component } from 'vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BirthdaySurprisePayload } from '@/types/finance';

const props = defineProps<{ surprise: BirthdaySurprisePayload }>();

type SurpriseSlide = {
    eyebrow: string;
    title: string;
    body: string;
    icon: Component;
};

const slides = computed<SurpriseSlide[]>(() => [
    {
        eyebrow: formattedStartDate.value,
        title: `Untuk ${props.surprise.recipientName} 🎂`,
        body: props.surprise.openingMessage,
        icon: CakeSlice,
    },
    {
        eyebrow: 'Cerita kita',
        title: 'Momen yang selalu ingin dikenang',
        body: props.surprise.appreciationMessage,
        icon: props.surprise.photos.length > 0 ? Images : Heart,
    },
    {
        eyebrow: `Surat dari ${props.surprise.senderName}`,
        title: `Untuk ${props.surprise.recipientName}, dengan penuh cinta`,
        body: props.surprise.loveLetter,
        icon: MailOpen,
    },
    {
        eyebrow: 'Hadiah yang bisa ditagih',
        title: `${props.surprise.vouchers.length} voucher khusus untukmu`,
        body: `Tunjukkan halaman ini kepada ${props.surprise.senderName} kapan pun kamu ingin menagihnya.`,
        icon: TicketCheck,
    },
    {
        eyebrow: 'Perjalanan kita',
        title: 'Masih banyak cerita yang menunggu',
        body: props.surprise.closingMessage,
        icon: Sparkles,
    },
]);

const confetti = [
    { left: '5%', delay: '0s', color: '#fb7185', duration: '3.2s' },
    { left: '14%', delay: '.7s', color: '#fbbf24', duration: '3.8s' },
    { left: '25%', delay: '.2s', color: '#a78bfa', duration: '3.5s' },
    { left: '36%', delay: '1.1s', color: '#34d399', duration: '4s' },
    { left: '48%', delay: '.4s', color: '#f472b6', duration: '3.3s' },
    { left: '59%', delay: '1.4s', color: '#60a5fa', duration: '3.9s' },
    { left: '70%', delay: '.1s', color: '#fb923c', duration: '3.6s' },
    { left: '81%', delay: '.9s', color: '#c084fc', duration: '4.1s' },
    { left: '92%', delay: '.5s', color: '#2dd4bf', duration: '3.4s' },
];
const galleryHearts = [
    { left: '16%', delay: '0s', duration: '4.8s' },
    { left: '48%', delay: '1.4s', duration: '5.4s' },
    { left: '78%', delay: '.6s', duration: '4.4s' },
];

const isOpen = ref(false);
const currentSlideIndex = ref(0);
const activePhotoIndex = ref(0);
const photoTransitionName = ref('memory-photo-next');
const slideTransitionName = ref('surprise-content-next');
let photoTouchStartX: number | null = null;

const currentSlide = computed(() => slides.value[currentSlideIndex.value]);
const activePhoto = computed(
    () => props.surprise.photos[activePhotoIndex.value] ?? null,
);
const isFirstSlide = computed(() => currentSlideIndex.value === 0);
const isLastSlide = computed(
    () => currentSlideIndex.value === slides.value.length - 1,
);
const storageKey = computed(
    () => `birthday-surprise:${props.surprise.id}:${props.surprise.startsAt}`,
);
const formattedStartDate = computed(() =>
    new Date(props.surprise.startsAt).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }),
);
const formattedEndDate = computed(() =>
    new Date(props.surprise.endsAt).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        timeZone: 'Asia/Jakarta',
    }),
);
const recipientInitial = computed(() =>
    props.surprise.recipientName.charAt(0).toUpperCase(),
);
const senderInitial = computed(() =>
    props.surprise.senderName.charAt(0).toUpperCase(),
);

function openSurprise(): void {
    currentSlideIndex.value = 0;
    activePhotoIndex.value = 0;
    photoTransitionName.value = 'memory-photo-next';
    slideTransitionName.value = 'surprise-content-next';
    isOpen.value = true;
}

function getJakartaDateKey(): string {
    const dateParts = new Intl.DateTimeFormat('en-US', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }).formatToParts(new Date());
    const year = dateParts.find((part) => part.type === 'year')?.value;
    const month = dateParts.find((part) => part.type === 'month')?.value;
    const day = dateParts.find((part) => part.type === 'day')?.value;

    return `${year}-${month}-${day}`;
}

function selectPhoto(index: number): void {
    photoTransitionName.value =
        index < activePhotoIndex.value
            ? 'memory-photo-previous'
            : 'memory-photo-next';
    activePhotoIndex.value = index;
}

function showPreviousPhoto(): void {
    const photoCount = props.surprise.photos.length;

    if (photoCount === 0) {
        return;
    }

    photoTransitionName.value = 'memory-photo-previous';
    activePhotoIndex.value =
        (activePhotoIndex.value - 1 + photoCount) % photoCount;
}

function showNextPhoto(): void {
    const photoCount = props.surprise.photos.length;

    if (photoCount === 0) {
        return;
    }

    photoTransitionName.value = 'memory-photo-next';
    activePhotoIndex.value = (activePhotoIndex.value + 1) % photoCount;
}

function startPhotoSwipe(event: TouchEvent): void {
    photoTouchStartX = event.touches.item(0)?.clientX ?? null;
}

function finishPhotoSwipe(event: TouchEvent): void {
    const touchEndX = event.changedTouches.item(0)?.clientX;

    if (photoTouchStartX === null || touchEndX === undefined) {
        photoTouchStartX = null;

        return;
    }

    const swipeDistance = touchEndX - photoTouchStartX;
    photoTouchStartX = null;

    if (Math.abs(swipeDistance) < 45) {
        return;
    }

    if (swipeDistance > 0) {
        showPreviousPhoto();

        return;
    }

    showNextPhoto();
}

function showPreviousSlide(): void {
    if (!isFirstSlide.value) {
        slideTransitionName.value = 'surprise-content-previous';
        currentSlideIndex.value--;
    }
}

function showNextSlide(): void {
    if (isLastSlide.value) {
        isOpen.value = false;

        return;
    }

    slideTransitionName.value = 'surprise-content-next';
    currentSlideIndex.value++;
}

onMounted(() => {
    const todayInJakarta = getJakartaDateKey();
    let lastAutomaticOpenDate: string | null = null;

    try {
        lastAutomaticOpenDate = window.localStorage.getItem(storageKey.value);
    } catch {}

    if (lastAutomaticOpenDate === todayInJakarta) {
        return;
    }

    try {
        window.localStorage.setItem(storageKey.value, todayInJakarta);
    } catch {}

    openSurprise();
});
</script>

<template>
    <div>
        <section
            class="relative overflow-hidden rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 via-fuchsia-50 to-indigo-50 p-4 shadow-sm dark:border-rose-900/60 dark:from-rose-950/50 dark:via-fuchsia-950/30 dark:to-indigo-950/40"
            aria-labelledby="birthday-banner-title"
        >
            <div
                class="pointer-events-none absolute -top-8 -right-8 h-28 w-28 rounded-full bg-rose-300/30 blur-2xl dark:bg-rose-500/20"
            />
            <div class="relative flex items-center gap-3">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-500 to-fuchsia-600 text-white shadow-lg shadow-rose-500/20"
                >
                    <Gift class="h-6 w-6" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-[10px] font-black tracking-widest text-rose-500 uppercase"
                    >
                        Khusus {{ surprise.recipientName }} 👑
                    </p>
                    <h2
                        id="birthday-banner-title"
                        class="truncate text-sm font-black text-zinc-900 dark:text-white"
                    >
                        Ada hadiah dari {{ surprise.senderName }}
                    </h2>
                    <p class="text-xs text-zinc-600 dark:text-zinc-300">
                        Bisa dibuka kembali sampai {{ formattedEndDate }}.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-rose-500 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-rose-600 focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 focus-visible:outline-none"
                    @click="openSurprise"
                >
                    Buka
                </button>
            </div>
        </section>

        <Dialog v-model:open="isOpen">
            <DialogContent
                class="max-h-[calc(100dvh-1rem)] max-w-[calc(100%-1rem)] overflow-hidden border-0 bg-transparent p-0 shadow-2xl sm:max-w-md"
                :show-close-button="false"
            >
                <DialogTitle class="sr-only">
                    Kejutan untuk {{ surprise.recipientName }}
                </DialogTitle>
                <DialogDescription class="sr-only">
                    Hadiah dari {{ surprise.senderName }} dalam lima halaman.
                </DialogDescription>

                <div
                    v-if="isOpen"
                    class="pointer-events-none fixed inset-0 z-10 overflow-hidden"
                    aria-hidden="true"
                >
                    <i
                        v-for="piece in confetti"
                        :key="piece.left"
                        class="birthday-confetti absolute -top-5 h-3 w-2 rounded-sm"
                        :style="{
                            left: piece.left,
                            backgroundColor: piece.color,
                            animationDelay: piece.delay,
                            animationDuration: piece.duration,
                        }"
                    />
                </div>

                <article
                    class="relative z-20 flex max-h-[calc(100dvh-1rem)] min-h-[min(620px,calc(100dvh-1rem))] flex-col overflow-y-auto rounded-3xl bg-gradient-to-b from-[#25113a] via-[#391233] to-[#111124] text-white"
                >
                    <DialogClose
                        class="absolute top-3 right-3 z-30 inline-flex min-h-11 min-w-11 items-center justify-center rounded-full bg-black/20 text-white/75 transition hover:bg-white/15 hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                        aria-label="Tutup kejutan"
                    >
                        <X class="h-5 w-5" aria-hidden="true" />
                    </DialogClose>
                    <div
                        class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[radial-gradient(circle_at_top,#f472b655,transparent_65%)]"
                    />

                    <div
                        class="relative flex flex-1 flex-col px-6 pt-14 pb-5 sm:px-8"
                    >
                        <div class="flex justify-center" aria-hidden="true">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 text-rose-300 ring-1 ring-white/15 backdrop-blur"
                            >
                                <component
                                    :is="currentSlide.icon"
                                    class="h-8 w-8"
                                />
                            </div>
                        </div>

                        <div
                            v-if="currentSlideIndex === 0"
                            class="mt-6 flex justify-center -space-x-3"
                        >
                            <div
                                v-for="person in [
                                    {
                                        name: surprise.senderName,
                                        avatar: surprise.senderAvatarUrl,
                                        initial: senderInitial,
                                        color: 'bg-amber-500',
                                    },
                                    {
                                        name: surprise.recipientName,
                                        avatar: surprise.recipientAvatarUrl,
                                        initial: recipientInitial,
                                        color: 'bg-rose-500',
                                    },
                                ]"
                                :key="person.name"
                                class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-4 border-[#351333] text-xl font-black shadow-xl"
                                :class="person.color"
                            >
                                <img
                                    v-if="person.avatar"
                                    :src="person.avatar"
                                    :alt="`Foto ${person.name}`"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ person.initial }}</span>
                            </div>
                        </div>

                        <div
                            class="mx-auto mt-6 flex max-w-sm flex-1 flex-col justify-center text-center"
                            aria-live="polite"
                        >
                            <Transition
                                :name="slideTransitionName"
                                mode="out-in"
                            >
                                <div
                                    :key="currentSlideIndex"
                                    class="flex flex-1 flex-col justify-center"
                                >
                                    <p
                                        class="text-[10px] font-black tracking-[0.22em] text-rose-300 uppercase"
                                    >
                                        {{ currentSlide.eyebrow }}
                                    </p>
                                    <h2
                                        class="mt-3 text-2xl leading-tight font-black tracking-tight sm:text-3xl"
                                    >
                                        {{ currentSlide.title }}
                                    </h2>

                                    <div
                                        v-if="
                                            currentSlideIndex === 1 &&
                                            surprise.photos.length &&
                                            activePhoto
                                        "
                                        class="memory-gallery mt-5"
                                    >
                                        <div
                                            class="memory-stage group relative h-[min(32dvh,17rem)] touch-pan-y overflow-hidden rounded-[1.75rem] bg-black/35 shadow-2xl ring-1 shadow-black/40 ring-white/15 sm:h-72"
                                            @touchstart.passive="
                                                startPhotoSwipe
                                            "
                                            @touchend.passive="finishPhotoSwipe"
                                        >
                                            <img
                                                :src="activePhoto"
                                                alt=""
                                                aria-hidden="true"
                                                class="memory-backdrop memory-photo absolute inset-0 h-full w-full scale-110 object-cover opacity-35 blur-2xl"
                                            />
                                            <div
                                                class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-black/55"
                                                aria-hidden="true"
                                            />

                                            <Transition
                                                :name="photoTransitionName"
                                                mode="out-in"
                                            >
                                                <div
                                                    :key="activePhoto"
                                                    class="relative z-10 h-full w-full"
                                                >
                                                    <img
                                                        :src="activePhoto"
                                                        :alt="`Kenangan kita, foto ${activePhotoIndex + 1} dari ${surprise.photos.length}`"
                                                        class="memory-focus memory-photo h-full w-full object-contain"
                                                    />
                                                </div>
                                            </Transition>

                                            <span
                                                v-for="heart in galleryHearts"
                                                :key="heart.left"
                                                class="memory-heart pointer-events-none absolute bottom-2 z-20 text-sm text-rose-300"
                                                :style="{
                                                    left: heart.left,
                                                    animationDelay: heart.delay,
                                                    animationDuration:
                                                        heart.duration,
                                                }"
                                                aria-hidden="true"
                                            >
                                                ♥
                                            </span>

                                            <div
                                                class="absolute inset-x-0 top-0 z-20 flex items-center justify-between p-3"
                                            >
                                                <span
                                                    class="rounded-full bg-black/35 px-3 py-1 text-[10px] font-black tracking-wider text-white/90 uppercase backdrop-blur-md"
                                                >
                                                    Kenangan kita
                                                </span>
                                                <span
                                                    class="rounded-full bg-black/35 px-2.5 py-1 text-[10px] font-bold text-white/90 tabular-nums backdrop-blur-md"
                                                >
                                                    {{ activePhotoIndex + 1 }} /
                                                    {{ surprise.photos.length }}
                                                </span>
                                            </div>

                                            <template
                                                v-if="
                                                    surprise.photos.length > 1
                                                "
                                            >
                                                <button
                                                    type="button"
                                                    class="absolute top-1/2 left-2 z-20 inline-flex min-h-11 min-w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-md transition hover:bg-black/55 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                                    aria-label="Lihat foto sebelumnya"
                                                    @click="showPreviousPhoto"
                                                >
                                                    <ChevronLeft
                                                        class="h-5 w-5"
                                                        aria-hidden="true"
                                                    />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="absolute top-1/2 right-2 z-20 inline-flex min-h-11 min-w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-md transition hover:bg-black/55 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                                    aria-label="Lihat foto berikutnya"
                                                    @click="showNextPhoto"
                                                >
                                                    <ChevronRight
                                                        class="h-5 w-5"
                                                        aria-hidden="true"
                                                    />
                                                </button>
                                            </template>
                                        </div>

                                        <div
                                            v-if="surprise.photos.length > 1"
                                            class="mt-3 flex justify-center gap-2 overflow-x-auto px-1 py-1"
                                            aria-label="Pilih foto kenangan"
                                        >
                                            <button
                                                v-for="(
                                                    photo, index
                                                ) in surprise.photos"
                                                :key="`${photo}-${index}`"
                                                type="button"
                                                class="relative h-12 w-12 shrink-0 overflow-hidden rounded-xl transition focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                                :class="
                                                    index === activePhotoIndex
                                                        ? 'memory-thumbnail-active scale-105 ring-2 ring-rose-400 ring-offset-2 ring-offset-[#351333]'
                                                        : 'opacity-55 ring-1 ring-white/15 hover:opacity-90'
                                                "
                                                :aria-label="`Tampilkan foto ${index + 1}`"
                                                :aria-current="
                                                    index === activePhotoIndex
                                                        ? 'true'
                                                        : undefined
                                                "
                                                @click="selectPhoto(index)"
                                            >
                                                <img
                                                    :src="photo"
                                                    alt=""
                                                    loading="lazy"
                                                    class="memory-photo h-full w-full object-cover"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        class="mt-4 text-sm leading-6 whitespace-pre-line text-white/75 sm:text-base sm:leading-7"
                                    >
                                        {{ currentSlide.body }}
                                    </p>

                                    <div
                                        v-if="
                                            currentSlideIndex === 3 &&
                                            surprise.vouchers.length
                                        "
                                        class="mt-5 grid gap-2 text-left"
                                    >
                                        <div
                                            v-for="voucher in surprise.vouchers"
                                            :key="voucher"
                                            class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/8 px-3 py-2.5 text-xs font-bold"
                                        >
                                            <TicketCheck
                                                class="h-4 w-4 shrink-0 text-amber-300"
                                                aria-hidden="true"
                                            />
                                            {{ voucher }}
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <div
                            class="mt-7 flex justify-center gap-1.5"
                            aria-hidden="true"
                        >
                            <span
                                v-for="(_, index) in slides"
                                :key="index"
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="
                                    index === currentSlideIndex
                                        ? 'w-7 bg-rose-400'
                                        : 'w-1.5 bg-white/25'
                                "
                            />
                        </div>

                        <div class="mt-5 flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-white/15 bg-white/8 text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-30"
                                :disabled="isFirstSlide"
                                aria-label="Halaman sebelumnya"
                                @click="showPreviousSlide"
                            >
                                <ChevronLeft
                                    class="h-5 w-5"
                                    aria-hidden="true"
                                />
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-rose-500 to-fuchsia-600 px-4 text-sm font-black text-white shadow-lg shadow-rose-950/40 transition hover:from-rose-400 hover:to-fuchsia-500 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#25113a] focus-visible:outline-none"
                                @click="showNextSlide"
                            >
                                <template v-if="isLastSlide">
                                    Peluk {{ surprise.senderName }} Sekarang 🤗
                                </template>
                                <template v-else>
                                    {{
                                        isFirstSlide ? 'Buka Hadiah' : 'Lanjut'
                                    }}
                                    <ChevronRight
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                </template>
                            </button>
                        </div>
                        <p class="mt-3 text-center text-[10px] text-white/45">
                            {{ currentSlideIndex + 1 }} dari {{ slides.length }}
                        </p>
                    </div>
                </article>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
@keyframes birthday-confetti-fall {
    0% {
        transform: translate3d(0, -5vh, 0) rotate(0deg);
        opacity: 0;
    }

    10% {
        opacity: 1;
    }

    100% {
        transform: translate3d(24px, 110vh, 0) rotate(720deg);
        opacity: 0.7;
    }
}

.birthday-confetti {
    animation-name: birthday-confetti-fall;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}

.memory-photo {
    image-orientation: from-image;
}

@keyframes memory-gallery-arrive {
    from {
        opacity: 0;
        transform: translateY(18px) scale(0.96);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes memory-backdrop-drift {
    from {
        transform: scale(1.12) translate3d(-1.5%, -1%, 0);
    }

    to {
        transform: scale(1.22) translate3d(1.5%, 1%, 0);
    }
}

@keyframes memory-focus-breathe {
    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.018);
    }
}

@keyframes memory-heart-float {
    0% {
        opacity: 0;
        transform: translate3d(0, 8px, 0) scale(0.65) rotate(-8deg);
    }

    20% {
        opacity: 0.8;
    }

    100% {
        opacity: 0;
        transform: translate3d(10px, -125px, 0) scale(1.15) rotate(12deg);
    }
}

@keyframes memory-thumbnail-pop {
    0% {
        transform: scale(0.88);
    }

    65% {
        transform: scale(1.12);
    }

    100% {
        transform: scale(1.05);
    }
}

.memory-gallery {
    animation: memory-gallery-arrive 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.memory-backdrop {
    animation: memory-backdrop-drift 10s ease-in-out infinite alternate;
}

.memory-focus {
    animation: memory-focus-breathe 7s ease-in-out infinite;
}

.memory-heart {
    animation: memory-heart-float linear infinite;
}

.memory-thumbnail-active {
    animation: memory-thumbnail-pop 320ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.memory-photo-next-enter-active,
.memory-photo-next-leave-active,
.memory-photo-previous-enter-active,
.memory-photo-previous-leave-active,
.surprise-content-next-enter-active,
.surprise-content-next-leave-active,
.surprise-content-previous-enter-active,
.surprise-content-previous-leave-active {
    transition:
        opacity 300ms ease,
        transform 360ms cubic-bezier(0.22, 1, 0.36, 1);
}

.memory-photo-next-enter-from,
.surprise-content-next-enter-from {
    opacity: 0;
    transform: translateX(24px) scale(0.97);
}

.memory-photo-next-leave-to,
.surprise-content-next-leave-to {
    opacity: 0;
    transform: translateX(-18px) scale(1.02);
}

.memory-photo-previous-enter-from,
.surprise-content-previous-enter-from {
    opacity: 0;
    transform: translateX(-24px) scale(0.97);
}

.memory-photo-previous-leave-to,
.surprise-content-previous-leave-to {
    opacity: 0;
    transform: translateX(18px) scale(1.02);
}

@media (prefers-reduced-motion: reduce) {
    .birthday-confetti {
        display: none;
    }

    .memory-gallery,
    .memory-backdrop,
    .memory-focus,
    .memory-heart,
    .memory-thumbnail-active {
        animation: none;
    }

    .memory-photo-next-enter-active,
    .memory-photo-next-leave-active,
    .memory-photo-previous-enter-active,
    .memory-photo-previous-leave-active,
    .surprise-content-next-enter-active,
    .surprise-content-next-leave-active,
    .surprise-content-previous-enter-active,
    .surprise-content-previous-leave-active {
        transition: none;
    }
}
</style>
