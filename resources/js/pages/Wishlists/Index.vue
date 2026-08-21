<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Gift, Plus, ExternalLink, Check, EyeOff, Sparkles, X, Edit2, Trash2 } from '@lucide/vue';
import type { Wallet, Category } from '@/types/finance';
import type { User } from '@/types/auth';

type WishlistItem = {
    id: number;
    title: string;
    estimated_price: string | number;
    priority: string;
    url: string | null;
    notes: string | null;
    is_secret_surprise: boolean;
    is_bought: boolean;
    user?: User;
};

const props = defineProps<{
    wishlists: WishlistItem[];
    wallets?: Wallet[];
    categories?: Category[];
    partner?: User | null;
    auth: {
        user: User;
    };
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingItem = ref<WishlistItem | null>(null);

const createForm = useForm({
    title: '',
    estimated_price: '' as string | number,
    priority: 'medium' as 'low' | 'medium' | 'high',
    url: '',
    notes: '',
    is_secret_surprise: false,
});

const editForm = useForm({
    title: '',
    estimated_price: '' as string | number,
    priority: 'medium' as 'low' | 'medium' | 'high',
    url: '',
    notes: '',
    is_secret_surprise: false,
});

function toggleBought(item: WishlistItem) {
    router.patch(`/wishlists/${item.id}/toggle`, {}, {
        preserveScroll: true,
    });
}

function openEditModal(item: WishlistItem) {
    editingItem.value = item;
    editForm.title = item.title;
    editForm.estimated_price = item.estimated_price;
    editForm.priority = item.priority as any;
    editForm.url = item.url || '';
    editForm.notes = item.notes || '';
    editForm.is_secret_surprise = item.is_secret_surprise;
    isEditModalOpen.value = true;
}

function submitCreate() {
    createForm.post('/wishlists', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function submitEdit() {
    if (!editingItem.value) return;
    editForm.put(`/wishlists/${editingItem.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingItem.value = null;
        },
    });
}

function deleteItem(item: WishlistItem) {
    if (confirm(`Hapus item wishlist "${item.title}"?`)) {
        router.delete(`/wishlists/${item.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Wishlist & Kado - Couple Finance" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Wishlist & Kado Impian
                </h1>
                <p class="text-xs text-zinc-500">Daftar barang idaman & kado kejutan rahasia</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-rose-500 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-600 transition-colors"
            >
                <Plus class="h-4 w-4" /> Tambah Item
            </button>
        </div>

        <!-- Secret Surprise Intro Banner -->
        <div class="rounded-3xl border border-rose-500/30 bg-gradient-to-r from-rose-500/10 via-pink-500/5 to-transparent p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-sm">
                    <Gift class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-xs font-bold text-rose-900 dark:text-rose-300">
                        Fitur Kado Kejutan Rahasia (Secret Surprise) 🎁
                    </h3>
                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Centang opsi <strong>"Kado Kejutan"</strong> saat membuat wishlist agar detailnya disamarkan dari pasangan sampai hari spesial tiba!
                    </p>
                </div>
            </div>
        </div>

        <!-- Wishlist Items Grid -->
        <div class="space-y-3">
            <div
                v-for="item in wishlists"
                :key="item.id"
                class="flex items-start justify-between rounded-3xl border p-4 shadow-sm transition-all dark:bg-zinc-900"
                :class="[
                    item.is_bought
                        ? 'border-emerald-500/30 bg-emerald-50/20 dark:border-emerald-500/20'
                        : item.is_secret_surprise
                            ? 'border-purple-500/30 bg-purple-50/10 dark:border-purple-500/20'
                            : 'border-zinc-200/80 bg-white dark:border-zinc-800',
                ]"
            >
                <div class="flex items-start gap-3 flex-1">
                    <!-- Toggle Bought Checkbox -->
                    <button
                        type="button"
                        @click="toggleBought(item)"
                        class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border transition-all"
                        :class="item.is_bought ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-zinc-300 hover:border-indigo-500 dark:border-zinc-700'"
                        title="Tandai sudah terbeli"
                    >
                        <Check v-if="item.is_bought" class="h-3.5 w-3.5 stroke-[3]" />
                    </button>

                    <div class="space-y-1 flex-1">
                        <div class="flex items-center gap-2">
                            <h3
                                class="text-sm font-bold text-zinc-900 dark:text-zinc-100"
                                :class="{ 'line-through text-zinc-400 dark:text-zinc-500': item.is_bought }"
                            >
                                {{ item.title }}
                            </h3>

                            <span
                                v-if="item.is_secret_surprise"
                                class="inline-flex items-center gap-1 rounded-full bg-purple-500/10 px-2 py-0.5 text-[10px] font-bold text-purple-600 dark:bg-purple-500/20 dark:text-purple-300"
                            >
                                <EyeOff class="h-3 w-3" /> Rahasia
                            </span>

                            <span
                                v-if="item.priority === 'high'"
                                class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                            >
                                Prioritas Tinggi
                            </span>
                        </div>

                        <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                            Rp {{ Number(item.estimated_price).toLocaleString('id-ID') }}
                        </p>

                        <p v-if="item.notes" class="text-xs text-zinc-500 italic">
                            "{{ item.notes }}"
                        </p>

                        <div class="flex items-center gap-3 pt-1 text-[11px] text-zinc-400">
                            <span v-if="item.user">Oleh: <strong>{{ item.user.nickname || item.user.name }}</strong></span>
                            <a
                                v-if="item.url"
                                :href="item.url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                Link Toko Online <ExternalLink class="h-3 w-3" />
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="openEditModal(item)"
                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                        title="Edit Wishlist"
                    >
                        <Edit2 class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        @click="deleteItem(item)"
                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                        title="Hapus Wishlist"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                v-if="wishlists.length === 0"
                class="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800"
            >
                Belum ada wishlist impian. Tambahkan item yang ingin kamu beli bareng pasangan!
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Tambah Wishlist Baru</h2>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="rounded-full p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Nama Barang / Kado</label>
                        <input
                            v-model="createForm.title"
                            type="text"
                            placeholder="Contoh: Sepatu Lari, Jam Tangan Couple, Tiket Konser"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Estimasi Harga (Rp)</label>
                        <input
                            v-model="createForm.estimated_price"
                            type="number"
                            placeholder="0"
                            min="0"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Prioritas</label>
                        <select
                            v-model="createForm.priority"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="low">Rendah (Santai)</option>
                            <option value="medium">Sedang (Dalam Waktu Dekat)</option>
                            <option value="high">Tinggi (Sangat Diinginkan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Link URL Produk (Opsional)</label>
                        <input
                            v-model="createForm.url"
                            type="url"
                            placeholder="https://shopee.co.id/..."
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Catatan Tambahan</label>
                        <input
                            v-model="createForm.notes"
                            type="text"
                            placeholder="Contoh: Ukuran 42 warna navy"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Secret Surprise Toggle -->
                    <label class="flex items-center gap-2.5 rounded-2xl border border-purple-200/80 bg-purple-50/50 p-3 text-xs dark:border-purple-900/50 dark:bg-purple-950/20 cursor-pointer">
                        <input
                            v-model="createForm.is_secret_surprise"
                            type="checkbox"
                            class="h-4 w-4 rounded text-purple-600 focus:ring-purple-500"
                        />
                        <span class="font-medium text-purple-900 dark:text-purple-300">
                            🎁 Rahasiakan dari pasangan (Kado Kejutan)
                        </span>
                    </label>

                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-rose-500 py-3 text-xs font-bold text-white shadow-md hover:bg-rose-600 transition-all"
                    >
                        Simpan Wishlist
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Wishlist</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Barang / Kado</label>
                        <input
                            v-model="editForm.title"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Estimasi Harga (Rp)</label>
                        <input
                            v-model="editForm.estimated_price"
                            type="number"
                            min="0"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Prioritas</label>
                        <select
                            v-model="editForm.priority"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="low">Rendah (Santai)</option>
                            <option value="medium">Sedang (Dalam Waktu Dekat)</option>
                            <option value="high">Tinggi (Sangat Diinginkan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Link URL Produk</label>
                        <input
                            v-model="editForm.url"
                            type="url"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Catatan Tambahan</label>
                        <input
                            v-model="editForm.notes"
                            type="text"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <label class="flex items-center gap-2.5 rounded-2xl border border-purple-200/80 bg-purple-50/50 p-3 text-xs dark:border-purple-900/50 dark:bg-purple-950/20 cursor-pointer">
                        <input
                            v-model="editForm.is_secret_surprise"
                            type="checkbox"
                            class="h-4 w-4 rounded text-purple-600 focus:ring-purple-500"
                        />
                        <span class="font-medium text-purple-900 dark:text-purple-300">
                            🎁 Rahasiakan dari pasangan (Kado Kejutan)
                        </span>
                    </label>

                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="w-full rounded-2xl bg-rose-500 py-3 text-xs font-bold text-white shadow-md hover:bg-rose-600 transition-all"
                    >
                        <Sparkles class="h-4 w-4 inline mr-1" />
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
