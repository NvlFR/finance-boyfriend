<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    X,
    Landmark,
    Smartphone,
    CreditCard,
    Coins,
    Check,
    Sparkles,
    TrendingUp,
    WalletCards,
    Shield,
    Users,
    User as UserIcon,
} from '@lucide/vue';
import WalletCard from '@/components/WalletCard.vue';
import type { Wallet, Category } from '@/types/finance';
import type { User } from '@/types/auth';

const props = defineProps<{
    his_wallets: Wallet[];
    her_wallets: Wallet[];
    joint_wallets: Wallet[];
    user_wallets: Wallet[];
    partner_wallets: Wallet[];
    total_net_worth: number;
    user_net_worth: number;
    partner_net_worth: number;
    joint_net_worth: number;
    partner?: User | null;
    categories?: Category[];
    wallets?: Wallet[];
    auth: {
        user: User;
    };
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingWallet = ref<Wallet | null>(null);

const walletTypes = [
    { type: 'bank', label: 'Bank Transfer', icon: Landmark, desc: 'BCA, Mandiri, BRI, BNI, Jago' },
    { type: 'ewallet', label: 'E-Wallet', icon: Smartphone, desc: 'GoPay, OVO, DANA, ShopeePay' },
    { type: 'cash', label: 'Uang Tunai', icon: Coins, desc: 'Dompet Fisik, Amplop Cash' },
    { type: 'investment', label: 'Investasi', icon: TrendingUp, desc: 'Bibit, Bareksa, Crypto, Saham' },
    { type: 'credit_card', label: 'Kartu Kredit', icon: CreditCard, desc: 'CC Paylater & Tagihan' },
];

const bankPresets = [
    { name: 'BCA', type: 'bank', color: '#00529C', label: 'BCA' },
    { name: 'Bank Mandiri', type: 'bank', color: '#002D62', label: 'Mandiri' },
    { name: 'Bank Jago', type: 'bank', color: '#FF7A00', label: 'Jago' },
    { name: 'BRI', type: 'bank', color: '#00529C', label: 'BRI' },
    { name: 'BNI', type: 'bank', color: '#006677', label: 'BNI' },
    { name: 'SeaBank', type: 'bank', color: '#EA580C', label: 'SeaBank' },
    { name: 'GoPay', type: 'ewallet', color: '#00AED6', label: 'GoPay' },
    { name: 'OVO', type: 'ewallet', color: '#4C3494', label: 'OVO' },
    { name: 'DANA', type: 'ewallet', color: '#118EEA', label: 'DANA' },
    { name: 'ShopeePay', type: 'ewallet', color: '#EE4D2D', label: 'ShopeePay' },
    { name: 'Uang Tunai', type: 'cash', color: '#10B981', label: 'Tunai / Cash' },
];

const colors = [
    '#6366F1', '#EC4899', '#F43F5E', '#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#14B8A6', '#00529C', '#4C3494', '#FF7A00'
];

const form = useForm({
    name: '',
    type: 'personal' as 'personal' | 'joint',
    wallet_type: 'bank' as 'bank' | 'ewallet' | 'cash' | 'investment' | 'credit_card',
    account_number: '',
    balance: '' as string | number,
    currency: 'IDR',
    color: '#6366F1',
    icon: 'wallet',
});

const editForm = useForm({
    name: '',
    type: 'personal' as 'personal' | 'joint',
    wallet_type: 'bank' as 'bank' | 'ewallet' | 'cash' | 'investment' | 'credit_card',
    account_number: '',
    balance: 0 as number,
    color: '#6366F1',
});

function applyPresetToCreate(preset: typeof bankPresets[0]) {
    form.name = preset.name;
    form.wallet_type = preset.type as any;
    form.color = preset.color;
}

function applyPresetToEdit(preset: typeof bankPresets[0]) {
    editForm.name = preset.name;
    editForm.wallet_type = preset.type as any;
    editForm.color = preset.color;
}

function submitWallet() {
    form.post('/wallets', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function handleEdit(w: Wallet) {
    editingWallet.value = w;
    editForm.name = w.name;
    editForm.type = w.type;
    editForm.wallet_type = w.wallet_type;
    editForm.account_number = w.account_number || '';
    editForm.balance = Number(w.balance);
    editForm.color = w.color || '#6366F1';
    isEditModalOpen.value = true;
}

function submitEditWallet() {
    if (!editingWallet.value) return;
    editForm.put(`/wallets/${editingWallet.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingWallet.value = null;
        },
    });
}

function handleDelete(w: Wallet) {
    if (confirm(`Apakah kamu yakin ingin menghapus dompet "${w.name}"? Saldo di dalamnya akan dihapus.`)) {
        router.delete(`/wallets/${w.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Dompet & Rekening - Couple Finance" />

    <div class="space-y-6">
        <!-- Top Bar Action -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Dompet & Rekening
                </h1>
                <p class="text-xs text-zinc-500">Kelola rekening bank, e-wallet, dan kas bersama pasangan</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-gradient-to-r from-indigo-600 to-rose-500 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 hover:opacity-95 transition-all"
            >
                <Plus class="h-4 w-4" /> Tambah Rekening / Dompet
            </button>
        </div>

        <!-- Net Worth Summary Banner -->
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-indigo-900 via-zinc-900 to-zinc-950 p-6 text-white shadow-xl dark:border-zinc-800">
            <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-rose-500/20 blur-2xl" />
            <div class="relative z-10 space-y-3">
                <span class="text-xs font-medium uppercase tracking-wider text-zinc-400">Total Saldo Gabungan (Net Worth)</span>
                <div class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                    Rp {{ Number(total_net_worth).toLocaleString('id-ID') }}
                </div>

                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-white/10 text-xs">
                    <div>
                        <span class="text-indigo-300 text-[11px]">Punya Kamu</span>
                        <p class="font-bold">Rp {{ Number(user_net_worth).toLocaleString('id-ID') }}</p>
                    </div>
                    <div>
                        <span class="text-rose-300 text-[11px]">{{ partner ? (partner.nickname || partner.name.split(' ')[0]) : 'Pasangan' }}</span>
                        <p class="font-bold">Rp {{ Number(partner_net_worth).toLocaleString('id-ID') }}</p>
                    </div>
                    <div>
                        <span class="text-emerald-300 text-[11px]">Kas Bersama</span>
                        <p class="font-bold">Rp {{ Number(joint_net_worth).toLocaleString('id-ID') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Joint Wallets Section -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Kas Bersama (Joint Wallets)
                    </h2>
                </div>
                <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100">
                    Rp {{ Number(joint_net_worth).toLocaleString('id-ID') }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <WalletCard
                    v-for="w in joint_wallets"
                    :key="w.id"
                    :wallet="w"
                    :is-joint="true"
                    @edit="handleEdit"
                    @delete="handleDelete"
                />
                <div
                    v-if="joint_wallets.length === 0"
                    class="col-span-full rounded-3xl border border-dashed border-zinc-200 p-8 text-center text-xs text-zinc-500 dark:border-zinc-800"
                >
                    Belum ada Kas Bersama. Buat dompet tipe "Kas Bersama" untuk patungan kencan dan bayar impian berdua!
                </div>
            </div>
        </section>

        <!-- Your Wallets Section -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-indigo-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        Dompet Pribadi Kamu
                    </h2>
                </div>
                <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100">
                    Rp {{ Number(user_net_worth).toLocaleString('id-ID') }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <WalletCard
                    v-for="w in user_wallets"
                    :key="w.id"
                    :wallet="w"
                    @edit="handleEdit"
                    @delete="handleDelete"
                />
                <div
                    v-if="user_wallets.length === 0"
                    class="col-span-full rounded-3xl border border-dashed border-zinc-200 p-6 text-center text-xs text-zinc-500 dark:border-zinc-800"
                >
                    Belum ada dompet pribadi yang terdaftar.
                </div>
            </div>
        </section>

        <!-- Partner Wallets Section -->
        <section v-if="partner" class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-rose-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                        Dompet {{ partner.nickname || partner.name }}
                    </h2>
                </div>
                <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100">
                    Rp {{ Number(partner_net_worth).toLocaleString('id-ID') }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <WalletCard
                    v-for="w in partner_wallets"
                    :key="w.id"
                    :wallet="w"
                    @edit="handleEdit"
                    @delete="handleDelete"
                />
                <div
                    v-if="partner_wallets.length === 0"
                    class="col-span-full rounded-3xl border border-dashed border-zinc-200 p-6 text-center text-xs text-zinc-500 dark:border-zinc-800"
                >
                    Pasangan belum menambahkan rekening pribadinya.
                </div>
            </div>
        </section>

        <!-- 🚀 Create Wallet Modal with Visual Bank / E-Wallet Selectors -->
        <div
            v-if="isCreateModalOpen"
            @click.self="isCreateModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm cursor-pointer"
        >
            <div
                @click.stop
                class="w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 max-h-[92vh] overflow-y-auto space-y-4 cursor-default"
            >
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                            <WalletCards class="h-4 w-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Tambah Dompet / Rekening</h2>
                            <p class="text-[11px] text-zinc-500">Pilih bank, e-wallet, atau kas bersama</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- 🌟 Live Virtual ATM / Debit Card Preview -->
                <div
                    class="relative overflow-hidden rounded-3xl p-5 text-white shadow-xl transition-all"
                    :style="{ backgroundColor: form.color || '#6366F1' }"
                >
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider backdrop-blur-md">
                            {{ form.type === 'joint' ? 'Kas Bersama' : 'Dompet Pribadi' }}
                        </span>
                        <Landmark v-if="form.wallet_type === 'bank'" class="h-5 w-5 opacity-80" />
                        <Smartphone v-else-if="form.wallet_type === 'ewallet'" class="h-5 w-5 opacity-80" />
                        <Coins v-else-if="form.wallet_type === 'cash'" class="h-5 w-5 opacity-80" />
                        <TrendingUp v-else-if="form.wallet_type === 'investment'" class="h-5 w-5 opacity-80" />
                        <CreditCard v-else class="h-5 w-5 opacity-80" />
                    </div>

                    <div class="mt-4">
                        <p class="text-[11px] opacity-80 uppercase tracking-widest font-mono">
                            {{ form.account_number ? form.account_number : '•••• •••• ••••' }}
                        </p>
                        <h3 class="text-lg font-black tracking-tight mt-0.5">
                            {{ form.name ? form.name : 'Nama Rekening / Bank' }}
                        </h3>
                    </div>

                    <div class="mt-3 flex items-baseline justify-between border-t border-white/20 pt-2 text-xs">
                        <span class="opacity-80">Saldo</span>
                        <span class="text-base font-extrabold">
                            Rp {{ Number(form.balance || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>
                </div>

                <!-- Quick Bank Presets Chips -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Preset Bank & E-Wallet Populer (1-Klik Isi)
                    </label>
                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                        <button
                            v-for="preset in bankPresets"
                            :key="preset.name"
                            type="button"
                            @click="applyPresetToCreate(preset)"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs font-semibold transition-all active:scale-95"
                            :class="[
                                form.name === preset.name
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/50 dark:text-indigo-300 ring-1 ring-indigo-500/20'
                                    : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'
                            ]"
                        >
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: preset.color }" />
                            <span>{{ preset.label }}</span>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitWallet" class="space-y-4">
                    <!-- Type (Personal vs Joint) -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Kepemilikan Kas</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.type = 'personal'"
                                class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                                :class="form.type === 'personal' ? 'border-indigo-600 bg-indigo-600/10 text-indigo-600 dark:border-indigo-400 dark:bg-indigo-950/40 dark:text-indigo-400 shadow-sm' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                <UserIcon class="h-4 w-4" /> Pribadi (Kamu)
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'joint'"
                                class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                                :class="form.type === 'joint' ? 'border-emerald-600 bg-emerald-600/10 text-emerald-600 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-400 shadow-sm' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                <Users class="h-4 w-4" /> Kas Bersama
                            </button>
                        </div>
                    </div>

                    <!-- Visual Service Type Grid (Replacing old static select) -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Jenis Layanan Finansial</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="t in walletTypes"
                                :key="t.type"
                                type="button"
                                @click="form.wallet_type = t.type as any"
                                class="flex flex-col items-center justify-center gap-1 rounded-2xl border p-2.5 text-center transition-all active:scale-95"
                                :class="[
                                    form.wallet_type === t.type
                                        ? 'border-indigo-600 bg-indigo-50/80 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/50 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-xs'
                                        : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                ]"
                            >
                                <component :is="t.icon" class="h-4 w-4" />
                                <span class="text-[11px] font-bold">{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nama Rekening / Dompet</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: BCA Utama, GoPay Kencan, Tabungan Liburan"
                            required
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Account Number -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nomor Rekening / No. HP E-Wallet (Opsional)</label>
                        <input
                            v-model="form.account_number"
                            type="text"
                            placeholder="Contoh: 1234567890 atau 08123456789"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Initial Balance -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Saldo Awal (Rp)</label>
                        <input
                            v-model="form.balance"
                            type="number"
                            placeholder="0"
                            min="0"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Color Picker -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Warna Kartu Dompet</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="form.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95 shadow-xs"
                                :style="{ backgroundColor: c }"
                            >
                                <Check v-if="form.color === c" class="h-3.5 w-3.5 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !form.name"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-rose-500 py-3.5 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 hover:opacity-95 transition-all disabled:opacity-50"
                    >
                        <Sparkles class="h-4 w-4" />
                        <span>Simpan Dompet Baru</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- ✏️ Edit Wallet Modal with Visual Bank / E-Wallet Selectors -->
        <div
            v-if="isEditModalOpen && editingWallet"
            @click.self="isEditModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm cursor-pointer"
        >
            <div
                @click.stop
                class="w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 max-h-[92vh] overflow-y-auto space-y-4 cursor-default"
            >
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Edit Dompet / Rekening</h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- 🌟 Live Virtual ATM / Debit Card Preview -->
                <div
                    class="relative overflow-hidden rounded-3xl p-5 text-white shadow-xl transition-all"
                    :style="{ backgroundColor: editForm.color || '#6366F1' }"
                >
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider backdrop-blur-md">
                            {{ editForm.type === 'joint' ? 'Kas Bersama' : 'Dompet Pribadi' }}
                        </span>
                        <Landmark v-if="editForm.wallet_type === 'bank'" class="h-5 w-5 opacity-80" />
                        <Smartphone v-else-if="editForm.wallet_type === 'ewallet'" class="h-5 w-5 opacity-80" />
                        <Coins v-else-if="editForm.wallet_type === 'cash'" class="h-5 w-5 opacity-80" />
                        <TrendingUp v-else-if="editForm.wallet_type === 'investment'" class="h-5 w-5 opacity-80" />
                        <CreditCard v-else class="h-5 w-5 opacity-80" />
                    </div>

                    <div class="mt-4">
                        <p class="text-[11px] opacity-80 uppercase tracking-widest font-mono">
                            {{ editForm.account_number ? editForm.account_number : '•••• •••• ••••' }}
                        </p>
                        <h3 class="text-lg font-black tracking-tight mt-0.5">
                            {{ editForm.name ? editForm.name : 'Nama Rekening / Bank' }}
                        </h3>
                    </div>

                    <div class="mt-3 flex items-baseline justify-between border-t border-white/20 pt-2 text-xs">
                        <span class="opacity-80">Saldo Saat Ini</span>
                        <span class="text-base font-extrabold">
                            Rp {{ Number(editForm.balance || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>
                </div>

                <!-- Quick Presets -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Preset Bank & E-Wallet Populer
                    </label>
                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                        <button
                            v-for="preset in bankPresets"
                            :key="preset.name"
                            type="button"
                            @click="applyPresetToEdit(preset)"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs font-semibold transition-all active:scale-95"
                            :class="[
                                editForm.name === preset.name
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/50 dark:text-indigo-300 ring-1 ring-indigo-500/20'
                                    : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'
                            ]"
                        >
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: preset.color }" />
                            <span>{{ preset.label }}</span>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitEditWallet" class="space-y-4">
                    <!-- Type (Personal vs Joint) -->
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Kepemilikan Kas</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="editForm.type = 'personal'"
                                class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                                :class="editForm.type === 'personal' ? 'border-indigo-600 bg-indigo-600/10 text-indigo-600 dark:border-indigo-400 dark:bg-indigo-950/40 dark:text-indigo-400 shadow-sm' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                <UserIcon class="h-4 w-4" /> Pribadi (Kamu)
                            </button>
                            <button
                                type="button"
                                @click="editForm.type = 'joint'"
                                class="flex items-center justify-center gap-2 rounded-2xl border p-2.5 text-xs font-bold transition-all"
                                :class="editForm.type === 'joint' ? 'border-emerald-600 bg-emerald-600/10 text-emerald-600 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-400 shadow-sm' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                <Users class="h-4 w-4" /> Kas Bersama
                            </button>
                        </div>
                    </div>

                    <!-- Visual Service Type Grid -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Jenis Layanan Finansial</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="t in walletTypes"
                                :key="t.type"
                                type="button"
                                @click="editForm.wallet_type = t.type as any"
                                class="flex flex-col items-center justify-center gap-1 rounded-2xl border p-2.5 text-center transition-all active:scale-95"
                                :class="[
                                    editForm.wallet_type === t.type
                                        ? 'border-indigo-600 bg-indigo-50/80 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950/50 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-xs'
                                        : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'
                                ]"
                            >
                                <component :is="t.icon" class="h-4 w-4" />
                                <span class="text-[11px] font-bold">{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nama Dompet</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Account Number -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Nomor Rekening</label>
                        <input
                            v-model="editForm.account_number"
                            type="text"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Balance -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Penyesuaian Saldo (Rp)</label>
                        <input
                            v-model="editForm.balance"
                            type="number"
                            min="0"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3.5 py-2.5 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Color Picker -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Warna Kartu Dompet</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="editForm.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95 shadow-xs"
                                :style="{ backgroundColor: c }"
                            >
                                <Check v-if="editForm.color === c" class="h-3.5 w-3.5 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="editForm.processing || !editForm.name"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3.5 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 transition-all disabled:opacity-50"
                    >
                        <Sparkles class="h-4 w-4" />
                        <span>Simpan Perubahan Dompet</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
