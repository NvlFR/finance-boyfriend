<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, X, Landmark, Smartphone, CreditCard, Coins, Check, Sparkles } from '@lucide/vue';
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

const colors = ['#6366F1', '#EC4899', '#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#14B8A6'];

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
    if (confirm(`Apakah kamu yakin ingin menghapus dompet "${w.name}"?`)) {
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
                <p class="text-xs text-zinc-500">Kelola rekening pribadi dan kas bersama</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
            >
                <Plus class="h-4 w-4" /> Tambah Dompet
            </button>
        </div>

        <!-- Net Worth Summary Banner -->
        <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <span class="text-xs font-medium uppercase tracking-wider text-zinc-400">Total Saldo Gabungan</span>
            <div class="mt-1 text-2xl font-extrabold text-zinc-900 dark:text-zinc-100">
                Rp {{ Number(total_net_worth).toLocaleString('id-ID') }}
            </div>
        </div>

        <!-- Joint Wallets Section -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Kas Bersama (Joint Wallets)
                    </h2>
                </div>
                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
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
                    class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-xs text-zinc-500 dark:border-zinc-800"
                >
                    Belum ada Kas Bersama. Buat dompet tipe "Kas Bersama" untuk patungan kencan!
                </div>
            </div>
        </section>

        <!-- Your Wallets Section -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-indigo-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        Dompet Kamu
                    </h2>
                </div>
                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                    Rp {{ Number(user_net_worth).toLocaleString('id-ID') }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <WalletCard
                    v-for="w in user_wallets"
                    :key="w.id"
                    :wallet="w"
                    :is-owner="true"
                    @edit="handleEdit"
                    @delete="handleDelete"
                />
                <div
                    v-if="user_wallets.length === 0"
                    class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-xs text-zinc-500 dark:border-zinc-800"
                >
                    Belum ada dompet pribadi.
                </div>
            </div>
        </section>

        <!-- Partner Wallets Section -->
        <section v-if="partner_wallets.length > 0" class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-rose-500" />
                    <h2 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                        Dompet Pasangan ({{ partner ? (partner.nickname || partner.name) : 'Pasangan' }})
                    </h2>
                </div>
                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
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
            </div>
        </section>

        <!-- Create Wallet Modal -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Tambah Dompet Baru</h2>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitWallet" class="mt-4 space-y-4">
                    <!-- Type (Personal vs Joint) -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Tipe Kepemilikan</label>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.type = 'personal'"
                                class="rounded-xl border p-2.5 text-xs font-medium transition-all"
                                :class="form.type === 'personal' ? 'border-indigo-600 bg-indigo-600/10 text-indigo-600' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                Pribadi (Punya Kamu)
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'joint'"
                                class="rounded-xl border p-2.5 text-xs font-medium transition-all"
                                :class="form.type === 'joint' ? 'border-emerald-600 bg-emerald-600/10 text-emerald-600' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                Kas Bersama (Patungan)
                            </button>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Nama Dompet / Rekening</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: BCA Utama, GoPay, Tabungan Liburan"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Category Type -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Jenis Layanan</label>
                        <select
                            v-model="form.wallet_type"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="bank">Bank Transfer</option>
                            <option value="ewallet">E-Wallet (GoPay, OVO, ShopeePay)</option>
                            <option value="cash">Uang Tunai / Cash</option>
                            <option value="investment">Investasi / Reksadana</option>
                            <option value="credit_card">Kartu Kredit</option>
                        </select>
                    </div>

                    <!-- Account Number -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Nomor Rekening / Catatan (Opsional)</label>
                        <input
                            v-model="form.account_number"
                            type="text"
                            placeholder="Contoh: 1234567890"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Initial Balance -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Saldo Awal (Rp)</label>
                        <input
                            v-model="form.balance"
                            type="number"
                            placeholder="0"
                            min="0"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Color Picker -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Warna Kartu</label>
                        <div class="mt-2 flex gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="form.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                            >
                                <Check v-if="form.color === c" class="h-3.5 w-3.5 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-500 transition-all disabled:opacity-50"
                    >
                        Simpan Dompet
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Wallet Modal -->
        <div
            v-if="isEditModalOpen && editingWallet"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Dompet</h2>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEditWallet" class="mt-4 space-y-4">
                    <!-- Type (Personal vs Joint) -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Tipe Kepemilikan</label>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="editForm.type = 'personal'"
                                class="rounded-xl border p-2.5 text-xs font-medium transition-all"
                                :class="editForm.type === 'personal' ? 'border-indigo-600 bg-indigo-600/10 text-indigo-600' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                Pribadi (Punya Kamu)
                            </button>
                            <button
                                type="button"
                                @click="editForm.type = 'joint'"
                                class="rounded-xl border p-2.5 text-xs font-medium transition-all"
                                :class="editForm.type === 'joint' ? 'border-emerald-600 bg-emerald-600/10 text-emerald-600' : 'border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400'"
                            >
                                Kas Bersama (Patungan)
                            </button>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Nama Dompet</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Category Type -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Jenis Layanan</label>
                        <select
                            v-model="editForm.wallet_type"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="bank">Bank Transfer</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="cash">Uang Tunai / Cash</option>
                            <option value="investment">Investasi</option>
                            <option value="credit_card">Kartu Kredit</option>
                        </select>
                    </div>

                    <!-- Account Number -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Nomor Rekening</label>
                        <input
                            v-model="editForm.account_number"
                            type="text"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Balance -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Saldo Saat Ini (Rp)</label>
                        <input
                            v-model="editForm.balance"
                            type="number"
                            min="0"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Color Picker -->
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Warna Kartu</label>
                        <div class="mt-2 flex gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="editForm.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                            >
                                <Check v-if="editForm.color === c" class="h-3.5 w-3.5 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-500 transition-all disabled:opacity-50"
                    >
                        <Sparkles class="h-4 w-4" />
                        <span>Simpan Perubahan</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
