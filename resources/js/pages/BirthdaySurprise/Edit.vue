<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarClock,
    Check,
    Clock3,
    Gift,
    Heart,
    ImagePlus,
    Save,
    Sparkles,
    Trash2,
    Upload,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import FormErrorSummary from '@/components/FormErrorSummary.vue';
import InputError from '@/components/InputError.vue';
import type { User } from '@/types/auth';

type ExistingPhoto = {
    path: string;
    url: string;
};

type PhotoPreview = {
    key: string;
    url: string;
    existing: boolean;
    path?: string;
    index?: number;
};

type SurpriseEditor = {
    id: number;
    openingMessage: string;
    appreciationMessage: string;
    loveLetter: string;
    closingMessage: string;
    photos: ExistingPhoto[];
    vouchers: string[];
    startsAt: string;
    endsAt: string;
    isEnabled: boolean;
    status: 'disabled' | 'scheduled' | 'active' | 'ended';
};

type Defaults = {
    openingMessage: string;
    appreciationMessage: string;
    loveLetter: string;
    closingMessage: string;
    startsAt: string;
    vouchers: string[];
};

const props = defineProps<{
    auth: { user: User };
    partner: User;
    surprise: SurpriseEditor | null;
    defaults: Defaults;
    durationDays: number;
    timezone: string;
}>();

const form = useForm({
    opening_message:
        props.surprise?.openingMessage ?? props.defaults.openingMessage,
    appreciation_message:
        props.surprise?.appreciationMessage ??
        props.defaults.appreciationMessage,
    love_letter: props.surprise?.loveLetter ?? props.defaults.loveLetter,
    closing_message:
        props.surprise?.closingMessage ?? props.defaults.closingMessage,
    starts_at: props.surprise?.startsAt ?? props.defaults.startsAt,
    is_enabled: props.surprise?.isEnabled ?? true,
    kept_photos: props.surprise?.photos.map((photo) => photo.path) ?? [],
    photos: [] as File[],
    vouchers: [0, 1, 2].map(
        (index) =>
            props.surprise?.vouchers[index] ??
            props.defaults.vouchers[index] ??
            '',
    ),
});

const newPhotoPreviews = ref<string[]>([]);

const keptExistingPhotos = computed(() =>
    (props.surprise?.photos ?? []).filter((photo) =>
        form.kept_photos.includes(photo.path),
    ),
);
const allPhotoPreviews = computed<PhotoPreview[]>(() => [
    ...keptExistingPhotos.value.map((photo) => ({
        key: photo.path,
        url: photo.url,
        existing: true,
        path: photo.path,
    })),
    ...newPhotoPreviews.value.map((url, index) => ({
        key: `${form.photos[index]?.name}-${index}`,
        url,
        existing: false,
        index,
    })),
]);
const remainingPhotoSlots = computed(() =>
    Math.max(0, 6 - keptExistingPhotos.value.length - form.photos.length),
);
const formattedEndPreview = computed(() => {
    if (!form.starts_at) {
        return '-';
    }

    const endDate = new Date(form.starts_at);
    endDate.setDate(endDate.getDate() + props.durationDays);

    return endDate.toLocaleString('id-ID', {
        dateStyle: 'long',
        timeStyle: 'short',
    });
});
const statusLabel = computed(() => {
    const labels = {
        disabled: 'Dinonaktifkan',
        scheduled: 'Terjadwal',
        active: 'Sedang aktif',
        ended: 'Sudah berakhir',
    };

    return props.surprise ? labels[props.surprise.status] : 'Belum disimpan';
});

function handlePhotoSelection(event: Event): void {
    const input = event.target as HTMLInputElement;
    const availableSlots = remainingPhotoSlots.value;
    const selectedPhotos = Array.from(input.files ?? []).slice(
        0,
        availableSlots,
    );

    form.clearErrors('photos');
    form.photos.push(...selectedPhotos);
    newPhotoPreviews.value.push(
        ...selectedPhotos.map((photo) => URL.createObjectURL(photo)),
    );

    if ((input.files?.length ?? 0) > availableSlots) {
        form.setError(
            'photos',
            `Hanya ${availableSlots} slot foto yang masih tersedia.`,
        );
    }

    input.value = '';
}

function removeExistingPhoto(path: string): void {
    form.kept_photos = form.kept_photos.filter(
        (photoPath) => photoPath !== path,
    );
}

function removeNewPhoto(index: number): void {
    URL.revokeObjectURL(newPhotoPreviews.value[index]);
    form.photos.splice(index, 1);
    newPhotoPreviews.value.splice(index, 1);
}

function removePhoto(photo: PhotoPreview): void {
    if (photo.existing && photo.path) {
        removeExistingPhoto(photo.path);

        return;
    }

    if (photo.index !== undefined) {
        removeNewPhoto(photo.index);
    }
}

function revokeNewPhotoPreviews(): void {
    newPhotoPreviews.value.forEach((url) => URL.revokeObjectURL(url));
    newPhotoPreviews.value = [];
}

function submit(): void {
    form.post('/birthday-surprise/settings', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const savedSurprise = page.props.surprise as SurpriseEditor | null;

            revokeNewPhotoPreviews();
            form.photos = [];
            form.kept_photos =
                savedSurprise?.photos.map((photo) => photo.path) ?? [];
        },
    });
}

onBeforeUnmount(revokeNewPhotoPreviews);
</script>

<template>
    <Head title="Atur Surprise" />

    <div class="mx-auto max-w-3xl space-y-5">
        <header class="flex items-center gap-3">
            <Link
                href="/couple-space"
                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200"
                aria-label="Kembali ke halaman pasangan"
            >
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div class="min-w-0">
                <p
                    class="text-[10px] font-black tracking-widest text-rose-500 uppercase"
                >
                    Rahasia dari {{ auth.user.nickname || auth.user.name }}
                </p>
                <h1
                    class="truncate text-xl font-black text-zinc-900 dark:text-white"
                >
                    Surprise untuk {{ partner.nickname || partner.name }}
                </h1>
                <p class="text-xs text-zinc-500">
                    Nama selalu mengikuti profil kalian di database.
                </p>
            </div>
        </header>

        <section
            class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#25113a] via-[#431633] to-[#15152b] p-5 text-white shadow-xl shadow-rose-950/10"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p
                        class="text-[10px] font-bold tracking-widest text-rose-300 uppercase"
                    >
                        Preview singkat
                    </p>
                    <h2 class="mt-1 text-xl font-black">
                        Untuk {{ partner.nickname || partner.name }} 🎂
                    </h2>
                    <p
                        class="mt-2 line-clamp-3 text-sm leading-6 text-white/70"
                    >
                        {{ form.opening_message }}
                    </p>
                </div>
                <div
                    class="rounded-2xl bg-white/10 p-3 text-rose-300 ring-1 ring-white/10"
                >
                    <Gift class="h-6 w-6" />
                </div>
            </div>

            <div v-if="allPhotoPreviews.length" class="mt-4 flex -space-x-2">
                <img
                    v-for="photo in allPhotoPreviews.slice(0, 5)"
                    :key="photo.key"
                    :src="photo.url"
                    alt="Preview foto surprise"
                    class="h-12 w-12 rounded-full border-2 border-[#321330] object-cover"
                />
                <span
                    v-if="allPhotoPreviews.length > 5"
                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-[#321330] bg-rose-500 text-xs font-black"
                >
                    +{{ allPhotoPreviews.length - 5 }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-xl bg-white/8 p-3 ring-1 ring-white/10">
                    <p class="text-white/50">Status</p>
                    <p class="mt-1 font-bold">{{ statusLabel }}</p>
                </div>
                <div class="rounded-xl bg-white/8 p-3 ring-1 ring-white/10">
                    <p class="text-white/50">Durasi aktif</p>
                    <p class="mt-1 font-bold">{{ durationDays }} hari penuh</p>
                </div>
            </div>
        </section>

        <form class="space-y-5" @submit.prevent="submit">
            <section
                class="space-y-4 rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="rounded-xl bg-fuchsia-500/10 p-2.5 text-fuchsia-600 dark:text-fuchsia-400"
                    >
                        <ImagePlus class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-black text-zinc-900 dark:text-white"
                        >
                            Foto Kenangan
                        </h2>
                        <p class="text-xs text-zinc-500">
                            Maksimal 6 foto, JPG/PNG/WebP, masing-masing 5 MB.
                        </p>
                    </div>
                </div>

                <div
                    v-if="allPhotoPreviews.length"
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                >
                    <div
                        v-for="photo in allPhotoPreviews"
                        :key="photo.key"
                        class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800"
                    >
                        <img
                            :src="photo.url"
                            alt="Foto surprise"
                            class="h-full w-full object-cover"
                        />
                        <button
                            type="button"
                            class="absolute top-2 right-2 inline-flex min-h-11 min-w-11 items-center justify-center rounded-full bg-black/65 text-white shadow-lg"
                            aria-label="Hapus foto"
                            @click="removePhoto(photo)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <label
                    v-if="allPhotoPreviews.length < 6"
                    class="flex min-h-24 cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-rose-200 bg-rose-50/60 px-4 py-5 text-center transition hover:border-rose-400 dark:border-rose-900 dark:bg-rose-950/20"
                >
                    <Upload class="h-6 w-6 text-rose-500" />
                    <span
                        class="text-xs font-bold text-zinc-800 dark:text-zinc-200"
                    >
                        Pilih {{ remainingPhotoSlots }} foto lagi
                    </span>
                    <input
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        @change="handlePhotoSelection"
                    />
                </label>
                <InputError :message="form.errors.photos" />
            </section>

            <section
                class="space-y-5 rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="rounded-xl bg-rose-500/10 p-2.5 text-rose-600 dark:text-rose-400"
                    >
                        <Heart class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-black text-zinc-900 dark:text-white"
                        >
                            Kata-kata dari Kamu
                        </h2>
                        <p class="text-xs text-zinc-500">
                            Isi ini hanya dikirim ke
                            {{ partner.nickname || partner.name }} saat jadwal
                            aktif.
                        </p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label
                        for="opening_message"
                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Kalimat Pembuka</label
                    >
                    <textarea
                        id="opening_message"
                        v-model="form.opening_message"
                        rows="3"
                        maxlength="300"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm focus:border-rose-500 focus:ring-rose-500 dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    <InputError :message="form.errors.opening_message" />
                </div>

                <div class="space-y-1.5">
                    <label
                        for="appreciation_message"
                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Cerita dan Apresiasi</label
                    >
                    <textarea
                        id="appreciation_message"
                        v-model="form.appreciation_message"
                        rows="4"
                        maxlength="1000"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm focus:border-rose-500 focus:ring-rose-500 dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    <InputError :message="form.errors.appreciation_message" />
                </div>

                <div class="space-y-1.5">
                    <label
                        for="love_letter"
                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Surat Cinta Utama</label
                    >
                    <textarea
                        id="love_letter"
                        v-model="form.love_letter"
                        rows="7"
                        maxlength="3000"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm leading-6 focus:border-rose-500 focus:ring-rose-500 dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    <InputError :message="form.errors.love_letter" />
                </div>

                <div class="space-y-1.5">
                    <label
                        for="closing_message"
                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Kalimat Penutup</label
                    >
                    <textarea
                        id="closing_message"
                        v-model="form.closing_message"
                        rows="4"
                        maxlength="1000"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm focus:border-rose-500 focus:ring-rose-500 dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    <InputError :message="form.errors.closing_message" />
                </div>
            </section>

            <section
                class="space-y-4 rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="rounded-xl bg-amber-500/10 p-2.5 text-amber-600 dark:text-amber-400"
                    >
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-black text-zinc-900 dark:text-white"
                        >
                            Voucher Hadiah
                        </h2>
                        <p class="text-xs text-zinc-500">
                            Opsional, maksimal tiga voucher.
                        </p>
                    </div>
                </div>
                <div
                    v-for="(_, index) in form.vouchers"
                    :key="index"
                    class="space-y-1.5"
                >
                    <label
                        :for="`voucher-${index}`"
                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Voucher {{ index + 1 }}</label
                    >
                    <input
                        :id="`voucher-${index}`"
                        v-model="form.vouchers[index]"
                        type="text"
                        maxlength="100"
                        class="min-h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    <InputError :message="form.errors[`vouchers.${index}`]" />
                </div>
            </section>

            <section
                class="space-y-4 rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="rounded-xl bg-indigo-500/10 p-2.5 text-indigo-600 dark:text-indigo-400"
                    >
                        <CalendarClock class="h-5 w-5" />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-black text-zinc-900 dark:text-white"
                        >
                            Jadwal Tayang
                        </h2>
                        <p class="text-xs text-zinc-500">
                            Waktu {{ timezone }} · aktif otomatis 7 hari.
                        </p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label
                        for="starts_at"
                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300"
                        >Mulai Tanggal dan Jam</label
                    >
                    <input
                        id="starts_at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        required
                        class="min-h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950"
                    />
                    <InputError :message="form.errors.starts_at" />
                </div>

                <div
                    class="flex items-start gap-3 rounded-2xl bg-indigo-50 p-4 text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200"
                >
                    <Clock3 class="mt-0.5 h-5 w-5 shrink-0" />
                    <div class="text-xs leading-5">
                        <p class="font-bold">Berakhir otomatis</p>
                        <p>
                            {{ formattedEndPreview }} setelah tujuh hari penuh.
                        </p>
                    </div>
                </div>

                <label
                    class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700"
                >
                    <div>
                        <p
                            class="text-sm font-bold text-zinc-900 dark:text-white"
                        >
                            Aktifkan surprise
                        </p>
                        <p class="text-xs text-zinc-500">
                            Matikan untuk menyimpan tanpa menayangkan.
                        </p>
                    </div>
                    <input
                        v-model="form.is_enabled"
                        type="checkbox"
                        class="h-6 w-6 rounded-md border-zinc-300 text-rose-500 focus:ring-rose-500"
                    />
                </label>
            </section>

            <FormErrorSummary :errors="form.errors" />

            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-rose-500 to-fuchsia-600 px-5 text-sm font-black text-white shadow-lg shadow-rose-500/20 transition hover:from-rose-600 hover:to-fuchsia-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <Save v-if="!form.processing" class="h-5 w-5" />
                <Check v-else class="h-5 w-5 animate-pulse" />
                {{
                    form.processing
                        ? 'Menyimpan Surprise...'
                        : 'Simpan dan Jadwalkan'
                }}
            </button>
        </form>
    </div>
</template>
