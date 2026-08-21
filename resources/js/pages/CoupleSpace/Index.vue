<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import {
    Heart,
    Sparkles,
    Copy,
    Check,
    Users,
    Shield,
    UserPlus,
    Calendar,
    Edit2,
    X,
    Flame,
    Share2,
    Send,
    Landmark,
    Target,
    Repeat,
    ArrowUpRight,
    HelpCircle,
} from '@lucide/vue';
import type { CoupleSpace } from '@/types/finance';
import type { User } from '@/types/auth';

type Stats = {
    joint_net_worth: number;
    active_goals_count: number;
    active_subscriptions_count: number;
    wishlists_count: number;
    transactions_count: number;
};

const props = defineProps<{
    coupleSpace?: CoupleSpace | null;
    partner?: User | null;
    stats?: Stats;
    auth: {
        user: User;
    };
}>();

const copied = ref(false);
const isEditModalOpen = ref(false);

const joinForm = useForm({
    invite_code: '',
});

const createForm = useForm({
    name: `${props.auth.user.name.split(' ')[0]} & Pasangan`,
    anniversary_date: '',
});

const editForm = useForm({
    name: props.coupleSpace?.name || '',
    anniversary_date: props.coupleSpace?.anniversary_date ? props.coupleSpace.anniversary_date.slice(0, 10) : '',
});

// Dynamic Love Counter
const daysTogether = computed(() => {
    if (!props.coupleSpace?.anniversary_date) return null;
    const anniv = new Date(props.coupleSpace.anniversary_date);
    const now = new Date();
    const diffTime = now.getTime() - anniv.getTime();
    if (diffTime < 0) return 0;
    return Math.floor(diffTime / (1000 * 60 * 60 * 24));
});

const whatsappShareUrl = computed(() => {
    if (!props.coupleSpace?.invite_code) return '#';
    const text = encodeURIComponent(
        `Hai sayang! 🥰 Yuk gabung ke ruang keuangan kita di Couple Finance dengan kode pairing: ${props.coupleSpace.invite_code}\n\nBuka aplikasinya di sini: ${window.location.origin}/couple-space`
    );
    return `https://api.whatsapp.com/send?text=${text}`;
});

function copyCode(code: string) {
    navigator.clipboard.writeText(code);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function handleJoin() {
    joinForm.post('/couple-space/join', {
        preserveScroll: true,
    });
}

function handleCreate() {
    createForm.post('/couple-space', {
        preserveScroll: true,
    });
}

function openEditModal() {
    if (!props.coupleSpace) return;
    editForm.name = props.coupleSpace.name;
    editForm.anniversary_date = props.coupleSpace.anniversary_date ? props.coupleSpace.anniversary_date.slice(0, 10) : '';
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!props.coupleSpace) return;
    editForm.put(`/couple-space/${props.coupleSpace.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Kelola Ruang Pasangan - Couple Finance" />

    <div class="space-y-6">
        <!-- Top Bar Action -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Ruang Pasangan (Couple Space)
                </h1>
                <p class="text-xs text-zinc-500">Kelola ruang romantis, statistik bersama, dan pairing</p>
            </div>

            <button
                v-if="coupleSpace"
                type="button"
                @click="openEditModal"
                class="flex items-center gap-1.5 rounded-full bg-rose-500/10 px-3.5 py-1.5 text-xs font-semibold text-rose-600 dark:bg-rose-500/20 dark:text-rose-300 hover:bg-rose-500/20 transition-colors"
            >
                <Edit2 class="h-3.5 w-3.5" /> Edit Ruang
            </button>
        </div>

        <!-- Paired Status Screen -->
        <div v-if="coupleSpace" class="space-y-6">
            <!-- Romantic Hero Banner with Live Love Counter -->
            <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-900/90 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800">
                <!-- Ambient Glowing Orbs -->
                <div class="absolute -right-8 -top-8 h-36 w-36 rounded-full bg-rose-500/20 blur-2xl" />
                <div class="absolute -left-8 -bottom-8 h-36 w-36 rounded-full bg-indigo-500/20 blur-2xl" />

                <div class="relative z-10 space-y-5">
                    <!-- Top Status -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-rose-500 animate-pulse" />
                            <span class="text-xs font-bold uppercase tracking-wider text-rose-300">
                                {{ partner ? 'Terhubung Romantis' : 'Menunggu Pairing' }}
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="openEditModal"
                            class="rounded-xl bg-white/10 p-2 text-white hover:bg-white/20 transition-all"
                            title="Edit Data Ruang"
                        >
                            <Edit2 class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Dual Couple Avatar Display -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                        <div class="flex items-center -space-x-3">
                            <!-- User Avatar -->
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white text-xl font-bold text-white shadow-lg ring-2 ring-white/20 overflow-hidden dark:border-zinc-800"
                                :style="{ backgroundColor: auth.user.theme_color || '#6366F1' }"
                            >
                                <img
                                    v-if="auth.user.avatar_url"
                                    :src="auth.user.avatar_url"
                                    alt="User Avatar"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ auth.user.nickname?.charAt(0) || auth.user.name.charAt(0) }}</span>
                            </div>

                            <!-- Heart Icon Badge -->
                            <div class="z-10 flex h-8 w-8 items-center justify-center rounded-full bg-rose-500 text-white shadow-md">
                                <Heart class="h-4 w-4 fill-current animate-bounce" />
                            </div>

                            <!-- Partner Avatar -->
                            <div
                                v-if="partner"
                                class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white text-xl font-bold text-white shadow-lg ring-2 ring-white/20 overflow-hidden dark:border-zinc-800"
                                :style="{ backgroundColor: partner.theme_color || '#F43F5E' }"
                            >
                                <img
                                    v-if="partner.avatar_url"
                                    :src="partner.avatar_url"
                                    alt="Partner Avatar"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ partner.nickname?.charAt(0) || partner.name.charAt(0) }}</span>
                            </div>

                            <div
                                v-else
                                class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-dashed border-white/50 bg-white/10 text-xs font-semibold text-white shadow-lg backdrop-blur-sm"
                            >
                                + Pasangan
                            </div>
                        </div>

                        <div class="space-y-1">
                            <h2 class="text-xl font-extrabold tracking-tight">
                                {{ coupleSpace.name }}
                            </h2>
                            <p class="text-xs text-zinc-300">
                                {{ partner ? `${auth.user.nickname || auth.user.name} & ${partner.nickname || partner.name}` : 'Bagikan kode ke pasangan untuk mulai kelola bersama' }}
                            </p>
                        </div>
                    </div>

                    <!-- Live Days Counter / Milestone Pill -->
                    <div
                        v-if="daysTogether !== null"
                        class="flex items-center justify-between rounded-2xl bg-white/10 p-3.5 border border-white/10 backdrop-blur-md"
                    >
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500 text-white">
                                <Flame class="h-4 w-4 fill-current" />
                            </div>
                            <div>
                                <p class="text-xs font-extrabold text-white">
                                    {{ daysTogether }} Hari Bersama
                                </p>
                                <p class="text-[10px] text-zinc-300">
                                    Sejak {{ new Date(coupleSpace.anniversary_date!).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </p>
                            </div>
                        </div>

                        <span class="rounded-full bg-rose-500/20 px-2.5 py-1 text-[11px] font-bold text-rose-200">
                            Romantis ❤️
                        </span>
                    </div>

                    <div
                        v-else
                        class="flex items-center justify-between rounded-2xl bg-white/5 p-3 border border-white/10"
                    >
                        <span class="text-xs text-zinc-400">Belum mengatur tanggal jadian / pernikahan?</span>
                        <button
                            type="button"
                            @click="openEditModal"
                            class="text-xs font-bold text-rose-300 hover:underline"
                        >
                            + Atur Tanggal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Invite Share Card (If Partner Not Connected Yet) -->
            <div
                v-if="!partner"
                class="rounded-3xl border border-indigo-200/80 bg-white p-5 shadow-sm dark:border-indigo-900/50 dark:bg-zinc-900 space-y-4"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-sm">
                        <Share2 class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            Undang Pasangan Bergabung
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Kirimkan kode pairing ini agar pasanganmu dapat menyambungkan akunnya
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-indigo-50/70 p-3.5 border border-indigo-100 dark:bg-indigo-950/30 dark:border-indigo-900/40">
                    <div>
                        <span class="text-[11px] font-semibold text-zinc-500">Kode Pairing:</span>
                        <p class="font-mono text-2xl font-black tracking-widest text-indigo-600 dark:text-indigo-400">
                            {{ coupleSpace.invite_code }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="copyCode(coupleSpace.invite_code)"
                        class="flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition-all"
                    >
                        <Check v-if="copied" class="h-3.5 w-3.5" />
                        <Copy v-else class="h-3.5 w-3.5" />
                        <span>{{ copied ? 'Tersalin!' : 'Salin' }}</span>
                    </button>
                </div>

                <!-- WhatsApp Quick Share Button -->
                <a
                    :href="whatsappShareUrl"
                    target="_blank"
                    rel="noopener"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-500 transition-all"
                >
                    <Send class="h-4 w-4" />
                    <span>Bagikan Kode ke WhatsApp Pasangan</span>
                </a>
            </div>

            <!-- Joint Financial Stats Grid -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                        Ringkasan Keuangan Ruang Pasangan
                    </h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- Kas Bersama -->
                    <Link
                        href="/wallets"
                        class="rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm hover:shadow-md transition-all dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <Landmark class="h-4 w-4" />
                        </div>
                        <p class="mt-3 text-[11px] text-zinc-400 font-medium">Kas Bersama</p>
                        <p class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100">
                            Rp {{ Number(stats?.joint_net_worth || 0).toLocaleString('id-ID') }}
                        </p>
                    </Link>

                    <!-- Target Impian -->
                    <Link
                        href="/goals"
                        class="rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm hover:shadow-md transition-all dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                            <Target class="h-4 w-4" />
                        </div>
                        <p class="mt-3 text-[11px] text-zinc-400 font-medium">Target Impian</p>
                        <p class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100">
                            {{ stats?.active_goals_count || 0 }} Target
                        </p>
                    </Link>

                    <!-- Langganan -->
                    <Link
                        href="/subscriptions"
                        class="rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm hover:shadow-md transition-all dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                            <Repeat class="h-4 w-4" />
                        </div>
                        <p class="mt-3 text-[11px] text-zinc-400 font-medium">Langganan Rutin</p>
                        <p class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100">
                            {{ stats?.active_subscriptions_count || 0 }} Tagihan
                        </p>
                    </Link>

                    <!-- Wishlist -->
                    <Link
                        href="/wishlists"
                        class="rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm hover:shadow-md transition-all dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                            <Heart class="h-4 w-4" />
                        </div>
                        <p class="mt-3 text-[11px] text-zinc-400 font-medium">Wishlist Kado</p>
                        <p class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100">
                            {{ stats?.wishlists_count || 0 }} Barang
                        </p>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Onboarding: Not in a couple space yet -->
        <div v-else class="space-y-6 max-w-lg mx-auto">
            <!-- Option A: Join Existing Space -->
            <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <div class="flex items-center gap-2">
                    <UserPlus class="h-5 w-5 text-indigo-500" />
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Gabung dengan Kode Undangan</h2>
                </div>
                <p class="text-xs text-zinc-500">
                    Jika pasanganmu sudah membuat ruang finansial, masukkan kode undangan di bawah:
                </p>

                <form @submit.prevent="handleJoin" class="space-y-3">
                    <input
                        v-model="joinForm.invite_code"
                        type="text"
                        placeholder="Contoh: AB12CD34"
                        required
                        class="w-full uppercase font-mono tracking-widest text-center text-lg font-bold rounded-2xl border border-zinc-300 bg-transparent py-3 px-4 text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:text-zinc-100"
                    />
                    <button
                        type="submit"
                        :disabled="joinForm.processing || !joinForm.invite_code"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-500 transition-all disabled:opacity-50"
                    >
                        Gabung Sekarang
                    </button>
                </form>
            </div>

            <div class="relative text-center">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-zinc-200 dark:border-zinc-800" /></div>
                <span class="relative bg-zinc-50 px-3 text-xs text-zinc-400 dark:bg-zinc-950">Atau</span>
            </div>

            <!-- Option B: Create New Space -->
            <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <div class="flex items-center gap-2">
                    <Sparkles class="h-5 w-5 text-rose-500" />
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Buat Ruang Baru</h2>
                </div>

                <form @submit.prevent="handleCreate" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nama Ruang</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:text-zinc-100"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-gradient-to-r from-rose-500 to-indigo-600 py-3 text-xs font-bold text-white shadow-md shadow-rose-500/20 hover:opacity-95 transition-all"
                    >
                        Buat & Dapatkan Kode Undangan
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Space Modal -->
        <div
            v-if="isEditModalOpen && coupleSpace"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Ruang Pasangan</h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nama Ruang Pasangan</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Tanggal Jadian / Pernikahan</label>
                        <input
                            v-model="editForm.anniversary_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        <Sparkles class="h-4 w-4 inline mr-1" />
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
