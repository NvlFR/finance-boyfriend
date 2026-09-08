<script setup lang="ts">
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import {
    Heart,
    Flame,
    Copy,
    Check,
    LogOut,
    Sparkles,
    Shield,
    Palette,
    Users,
    Camera,
    Tag,
    ChevronDown,
} from '@lucide/vue';
import { ref, computed, onBeforeUnmount } from 'vue';
import InputError from '@/components/InputError.vue';
import { useLogoutModal } from '@/composables/useLogoutModal';
import { update as avatarUpdate } from '@/routes/profile/avatar';
import type { User } from '@/types/auth';
import type { CoupleSpace } from '@/types/finance';

const page = usePage();
const user = computed(() => page.props.auth.user as User);
const coupleSpace = computed(
    () => (page.props as any).coupleSpace as CoupleSpace | null,
);
const partner = computed(() => (page.props as any).partner as User | null);

const isCopied = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const isProfileDetailsOpen = ref(false);

const themeColors = [
    { label: 'Indigo', value: '#6366F1' },
    { label: 'Rose', value: '#F43F5E' },
    { label: 'Pink', value: '#EC4899' },
    { label: 'Emerald', value: '#10B981' },
    { label: 'Amber', value: '#F59E0B' },
    { label: 'Blue', value: '#3B82F6' },
    { label: 'Purple', value: '#8B5CF6' },
    { label: 'Teal', value: '#14B8A6' },
];

const form = useForm({
    name: user.value.name,
    nickname: user.value.nickname || '',
    email: user.value.email,
    theme_color: user.value.theme_color || '#6366F1',
});

const avatarForm = useForm({
    avatar: null as File | null,
});

function clearAvatarPreview() {
    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
        avatarPreview.value = null;
    }
}

function handleFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];

        clearAvatarPreview();
        avatarPreview.value = URL.createObjectURL(file);
        avatarForm.avatar = file;
        avatarForm.post(avatarUpdate.url(), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                clearAvatarPreview();
                avatarForm.reset('avatar');
            },
            onError: () => clearAvatarPreview(),
            onFinish: () => {
                target.value = '';
            },
        });
    }
}

onBeforeUnmount(clearAvatarPreview);

function triggerFileInput() {
    fileInput.value?.click();
}

function copyInviteCode() {
    if (!coupleSpace.value?.invite_code) {
        return;
    }

    navigator.clipboard.writeText(coupleSpace.value.invite_code);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}

function submitProfile() {
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post('/settings/profile', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            isProfileDetailsOpen.value = false;
        },
    });
}

const { openLogoutModal } = useLogoutModal();

function handleLogout() {
    openLogoutModal();
}
</script>

<template>
    <Head title="Profil & Pengaturan Akun" />

    <div class="space-y-6">
        <!-- Couple Status Hero Card -->
        <div
            class="relative isolate overflow-hidden rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-900/90 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800"
        >
            <img
                v-if="coupleSpace?.dashboard_cover_url"
                :src="coupleSpace.dashboard_cover_url"
                alt=""
                class="absolute inset-0 z-0 h-full w-full object-cover"
            />
            <div
                v-if="coupleSpace?.dashboard_cover_url"
                class="absolute inset-0 z-[1] bg-gradient-to-br from-slate-950/85 via-zinc-950/70 to-rose-950/75"
            />

            <!-- Ambient Glow -->
            <div
                class="absolute -top-8 -right-8 z-[2] h-32 w-32 rounded-full bg-rose-500/20 blur-2xl"
            />
            <div
                class="absolute -bottom-8 -left-8 z-[2] h-32 w-32 rounded-full bg-indigo-500/20 blur-2xl"
            />

            <div
                class="relative z-10 flex flex-col items-center gap-4 text-center sm:flex-row sm:items-start sm:text-left"
            >
                <!-- Dual Avatar Header with Photo Support -->
                <div class="flex items-center -space-x-3">
                    <!-- User Avatar -->
                    <div
                        @click="triggerFileInput"
                        class="group relative cursor-pointer"
                        title="Klik untuk ubah foto profil"
                    >
                        <div
                            class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-white text-xl font-bold text-white shadow-lg ring-2 ring-white/20"
                            :style="{
                                backgroundColor: form.theme_color || '#6366F1',
                            }"
                        >
                            <img
                                v-if="avatarPreview || user.avatar_url"
                                :src="
                                    avatarPreview ||
                                    user.avatar_url ||
                                    undefined
                                "
                                alt="Foto Profil"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{
                                user.nickname?.charAt(0) || user.name.charAt(0)
                            }}</span>
                        </div>

                        <!-- Camera Badge -->
                        <div
                            class="absolute -right-1 -bottom-1 rounded-full border border-white/40 bg-zinc-900/90 p-1 text-white shadow-md transition-colors hover:bg-rose-500"
                        >
                            <Camera class="h-3.5 w-3.5" />
                        </div>
                    </div>

                    <!-- Heart Connector -->
                    <div
                        class="z-10 flex h-7 w-7 items-center justify-center rounded-full bg-rose-500 text-white shadow-md"
                    >
                        <Heart class="h-4 w-4 fill-current" />
                    </div>

                    <!-- Partner Avatar -->
                    <div
                        v-if="partner"
                        class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-rose-500 text-xl font-bold text-white shadow-lg ring-2 ring-white/20"
                        :style="{
                            backgroundColor: partner.theme_color || '#F43F5E',
                        }"
                    >
                        <img
                            v-if="partner.avatar_url"
                            :src="partner.avatar_url"
                            alt="Foto Pasangan"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{
                            partner.nickname?.charAt(0) ||
                            partner.name.charAt(0)
                        }}</span>
                    </div>

                    <div
                        v-else
                        class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-dashed border-white/50 bg-white/10 text-xs font-medium text-white shadow-lg"
                    >
                        + Pasangan
                    </div>
                </div>

                <div class="flex-1 space-y-1">
                    <div
                        class="flex flex-wrap items-center justify-center gap-2 sm:justify-start"
                    >
                        <h2 class="text-lg font-bold">
                            {{ user.nickname || user.name }}
                        </h2>
                        <span class="text-sm text-zinc-400">&</span>
                        <h2 class="text-lg font-bold text-rose-300">
                            {{
                                partner
                                    ? partner.nickname || partner.name
                                    : 'Menunggu Pasangan...'
                            }}
                        </h2>
                    </div>

                    <p class="text-xs text-zinc-300">
                        {{ coupleSpace?.name || 'Couple Finance Space' }}
                    </p>

                    <!-- Status Pill -->
                    <div class="pt-1">
                        <div
                            v-if="partner"
                            class="inline-flex items-center gap-1.5 rounded-full border border-rose-500/30 bg-rose-500/20 px-3 py-1 text-xs font-semibold text-rose-200"
                        >
                            <Flame
                                class="h-3.5 w-3.5 animate-pulse fill-current text-rose-400"
                            />
                            <span
                                >Terhubung Romantis Sejak
                                {{
                                    coupleSpace?.anniversary_date
                                        ? new Date(
                                              coupleSpace.anniversary_date,
                                          ).toLocaleDateString('id-ID', {
                                              day: 'numeric',
                                              month: 'short',
                                              year: 'numeric',
                                          })
                                        : 'Hari Ini'
                                }}</span
                            >
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center gap-2 sm:flex-row"
                        >
                            <span class="text-xs text-rose-300"
                                >Belum terhubung dengan pasangan</span
                            >
                            <Link
                                href="/couple-space"
                                class="inline-flex items-center gap-1 rounded-full bg-rose-500 px-3 py-1 text-xs font-bold text-white shadow-sm hover:bg-rose-600"
                            >
                                Hubungkan Sekarang
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invite code is only needed until a partner joins. -->
            <div
                v-if="coupleSpace?.invite_code && !partner"
                class="relative z-10 mt-5 flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-2.5 backdrop-blur-md"
            >
                <span class="text-xs text-zinc-400"
                    >Kode Pairing:
                    <strong class="tracking-widest text-white">{{
                        coupleSpace.invite_code
                    }}</strong></span
                >
                <button
                    type="button"
                    @click="copyInviteCode"
                    class="flex items-center gap-1 rounded-xl bg-white/10 px-2.5 py-1 text-[11px] font-semibold text-white transition-all hover:bg-white/20"
                >
                    <Check
                        v-if="isCopied"
                        class="h-3.5 w-3.5 text-emerald-400"
                    />
                    <Copy v-else class="h-3.5 w-3.5" />
                    <span>{{ isCopied ? 'Tersalin!' : 'Salin Kode' }}</span>
                </button>
            </div>
        </div>

        <!-- Form Edit Data Diri -->
        <section
            class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
            <button
                type="button"
                class="flex min-h-20 w-full items-center justify-between gap-3 p-4 text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70"
                :aria-expanded="isProfileDetailsOpen"
                aria-controls="profile-details-panel"
                @click="isProfileDetailsOpen = !isProfileDetailsOpen"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-sm font-bold text-white shadow-sm"
                        :style="{
                            backgroundColor: form.theme_color || '#6366F1',
                        }"
                    >
                        <img
                            v-if="avatarPreview || user.avatar_url"
                            :src="avatarPreview || user.avatar_url || undefined"
                            alt="Foto profil"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{
                            user.nickname?.charAt(0) || user.name.charAt(0)
                        }}</span>
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Informasi Data Diri
                        </h3>
                        <p class="truncate text-[11px] text-zinc-500">
                            {{ user.nickname || user.name }} · {{ user.email }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span
                        class="hidden rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-bold text-indigo-600 sm:inline dark:bg-indigo-500/15 dark:text-indigo-300"
                    >
                        Edit profil
                    </span>
                    <ChevronDown
                        class="h-5 w-5 text-zinc-400 transition-transform duration-200"
                        :class="isProfileDetailsOpen ? 'rotate-180' : ''"
                    />
                </div>
            </button>

            <div
                id="profile-details-panel"
                v-show="isProfileDetailsOpen"
                class="border-t border-zinc-100 px-4 pt-5 pb-4 sm:px-6 sm:pb-6 dark:border-zinc-800"
            >
                <div class="mb-5">
                    <p
                        class="text-xs font-semibold text-zinc-800 dark:text-zinc-200"
                    >
                        Edit profil kamu
                    </p>
                    <p class="mt-0.5 text-[11px] text-zinc-500">
                        Foto, nama, email, dan warna aksen aplikasi
                    </p>
                </div>

                <form @submit.prevent="submitProfile" class="space-y-4">
                    <!-- Hidden File Input for Avatar -->
                    <input
                        ref="fileInput"
                        type="file"
                        :disabled="avatarForm.processing"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        @change="handleFileSelect"
                        class="hidden"
                    />

                    <!-- Centered Modern Profile Picture Picker -->
                    <div
                        class="flex flex-col items-center justify-center py-2 text-center"
                    >
                        <div
                            @click="triggerFileInput"
                            class="group relative cursor-pointer"
                            title="Klik untuk memilih foto profil"
                        >
                            <div
                                class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-white shadow-xl ring-4 ring-indigo-500/20 transition-transform group-hover:scale-105 dark:border-zinc-800"
                                :style="{
                                    backgroundColor:
                                        form.theme_color || '#6366F1',
                                }"
                            >
                                <img
                                    v-if="avatarPreview || user.avatar_url"
                                    :src="
                                        avatarPreview ||
                                        user.avatar_url ||
                                        undefined
                                    "
                                    alt="Foto Profil"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="text-3xl font-extrabold text-white"
                                >
                                    {{
                                        user.nickname?.charAt(0) ||
                                        user.name.charAt(0)
                                    }}
                                </span>
                            </div>

                            <!-- Camera Action Pill Overlay -->
                            <div
                                class="absolute right-0 bottom-0 flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg ring-2 ring-white transition-all group-hover:bg-indigo-500 dark:ring-zinc-900"
                            >
                                <Camera class="h-4 w-4" />
                            </div>
                        </div>

                        <div class="mt-3 space-y-0.5">
                            <button
                                type="button"
                                @click="triggerFileInput"
                                class="text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                {{
                                    avatarPreview || user.avatar_url
                                        ? 'Ganti Foto Profil'
                                        : 'Upload Foto Profil'
                                }}
                            </button>
                            <p class="text-[11px] text-zinc-400">
                                {{
                                    avatarForm.processing
                                        ? 'Sedang mengunggah foto...'
                                        : 'Format JPG, PNG, atau WebP (Maks. 2MB) · otomatis tersimpan'
                                }}
                            </p>
                            <progress
                                v-if="avatarForm.progress"
                                :value="avatarForm.progress.percentage"
                                max="100"
                                class="mt-2 h-1.5 w-32 overflow-hidden rounded-full"
                            />
                        </div>

                        <InputError
                            :message="avatarForm.errors.avatar"
                            class="mt-2"
                        />
                    </div>

                    <!-- Name -->
                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-500 dark:text-zinc-400"
                            >Nama Lengkap</label
                        >
                        <div class="relative mt-1">
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm font-medium text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
                            />
                        </div>
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Nickname -->
                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-500 dark:text-zinc-400"
                        >
                            Nama Panggilan / Nickname (Untuk Tampilan Kencan)
                        </label>
                        <div class="relative mt-1">
                            <input
                                v-model="form.nickname"
                                type="text"
                                placeholder="Contoh: Rony, Ayang, Babe"
                                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm font-medium text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
                            />
                        </div>
                        <InputError
                            :message="form.errors.nickname"
                            class="mt-1"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-500 dark:text-zinc-400"
                            >Alamat Email</label
                        >
                        <div class="relative mt-1">
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-2.5 text-sm font-medium text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100 dark:focus:bg-zinc-900"
                            />
                        </div>
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>

                    <!-- Theme Color Picker -->
                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-500 dark:text-zinc-400"
                        >
                            Warna Aksen Tema Kamu
                        </label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="color in themeColors"
                                :key="color.value"
                                type="button"
                                @click="form.theme_color = color.value"
                                class="group relative flex h-9 w-9 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: color.value }"
                            >
                                <Check
                                    v-if="form.theme_color === color.value"
                                    class="h-4 w-4 text-white drop-shadow-md"
                                />
                            </button>
                        </div>
                        <InputError
                            :message="form.errors.theme_color"
                            class="mt-1"
                        />
                    </div>

                    <!-- Save Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition-all hover:bg-indigo-500 disabled:opacity-50"
                        >
                            <Sparkles class="h-4 w-4" />
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Shortcut Pengaturan Lainnya -->
        <div
            class="space-y-2 rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
            <h4
                class="px-2 pt-1 text-xs font-bold tracking-wider text-zinc-400 uppercase"
            >
                Menu Pengaturan
            </h4>

            <Link
                href="/settings/security"
                class="flex items-center justify-between rounded-2xl p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                    >
                        <Shield class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Keamanan & Sandi
                        </p>
                        <p class="text-[11px] text-zinc-500">
                            Ubah password, 2FA, dan Passkey
                        </p>
                    </div>
                </div>
                <span class="text-xs text-zinc-400">&rarr;</span>
            </Link>

            <Link
                href="/settings/appearance"
                class="flex items-center justify-between rounded-2xl p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                    >
                        <Palette class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Tampilan Aplikasi
                        </p>
                        <p class="text-[11px] text-zinc-500">
                            Mode Terang, Gelap, atau Otomatis
                        </p>
                    </div>
                </div>
                <span class="text-xs text-zinc-400">&rarr;</span>
            </Link>

            <Link
                href="/couple-space"
                class="flex items-center justify-between rounded-2xl p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Kelola Ruang Pasangan
                        </p>
                        <p class="text-[11px] text-zinc-500">
                            Kode pairing & status hubungan
                        </p>
                    </div>
                </div>
                <span class="text-xs text-zinc-400">&rarr;</span>
            </Link>

            <Link
                href="/categories"
                class="flex items-center justify-between rounded-2xl p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400"
                    >
                        <Tag class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-zinc-900 dark:text-zinc-100"
                        >
                            Kategori Keuangan
                        </p>
                        <p class="text-[11px] text-zinc-500">
                            Kelola kategori pengeluaran & kencan
                        </p>
                    </div>
                </div>
                <span class="text-xs text-zinc-400">&rarr;</span>
            </Link>
        </div>

        <!-- Logout Action Button -->
        <button
            type="button"
            @click="handleLogout"
            class="flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50/50 py-3 text-sm font-bold text-rose-600 transition-colors hover:bg-rose-100/80 dark:border-rose-950/60 dark:bg-rose-950/20 dark:text-rose-400 dark:hover:bg-rose-950/40"
        >
            <LogOut class="h-4 w-4" />
            <span>Keluar dari Akun</span>
        </button>
    </div>
</template>
