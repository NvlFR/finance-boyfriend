<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Tag, Plus, Check, Edit2, Trash2, X, Sparkles, Lock } from '@lucide/vue';
import type { Category } from '@/types/finance';

const props = defineProps<{
    categories: Category[];
    income_categories: Category[];
    expense_categories: Category[];
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingCategory = ref<Category | null>(null);

const colors = [
    '#6366F1', '#F43F5E', '#EC4899', '#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#14B8A6', '#64748B'
];

const createForm = useForm({
    name: '',
    type: 'expense' as 'expense' | 'income' | 'both',
    color: '#6366F1',
    icon: 'tag',
});

const editForm = useForm({
    name: '',
    type: 'expense' as 'expense' | 'income' | 'both',
    color: '#6366F1',
    icon: 'tag',
});

function submitCreate() {
    createForm.post('/categories', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

function openEditModal(cat: Category) {
    editingCategory.value = cat;
    editForm.name = cat.name;
    editForm.type = cat.type as any;
    editForm.color = cat.color || '#6366F1';
    editForm.icon = cat.icon || 'tag';
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!editingCategory.value) return;
    editForm.put(`/categories/${editingCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingCategory.value = null;
        },
    });
}

function deleteCategory(cat: Category) {
    if (confirm(`Hapus kategori kustom "${cat.name}"?`)) {
        router.delete(`/categories/${cat.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Kategori Keuangan - Couple Finance" />

    <div class="space-y-6">
        <!-- Top Bar Action -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                    Kategori Keuangan
                </h1>
                <p class="text-xs text-zinc-500">Kelola kategori pengeluaran dan pemasukan bersama</p>
            </div>

            <button
                type="button"
                @click="isCreateModalOpen = true"
                class="flex items-center gap-1.5 rounded-full bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
            >
                <Plus class="h-4 w-4" /> Tambah Kategori
            </button>
        </div>

        <!-- Expense Categories Section -->
        <div class="space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-rose-500">
                Kategori Pengeluaran & Kencan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div
                    v-for="cat in expense_categories"
                    :key="cat.id"
                    class="flex items-center justify-between rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl text-white shadow-sm"
                            :style="{ backgroundColor: cat.color || '#F43F5E' }"
                        >
                            <Tag class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                {{ cat.name }}
                            </h3>
                            <span class="text-[10px] text-zinc-400">
                                {{ cat.is_default ? 'Kategori Bawaan' : 'Kategori Kustom Pasangan' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-if="!cat.is_default">
                            <button
                                type="button"
                                @click="openEditModal(cat)"
                                class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 transition-colors"
                                title="Edit Kategori"
                            >
                                <Edit2 class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                @click="deleteCategory(cat)"
                                class="rounded-lg p-1 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                                title="Hapus Kategori"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </template>
                        <Lock v-else class="h-3.5 w-3.5 text-zinc-300 dark:text-zinc-600" title="Kategori Sistem Default" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Categories Section -->
        <div class="space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-emerald-500">
                Kategori Pemasukan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div
                    v-for="cat in income_categories"
                    :key="cat.id"
                    class="flex items-center justify-between rounded-3xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl text-white shadow-sm"
                            :style="{ backgroundColor: cat.color || '#10B981' }"
                        >
                            <Tag class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                {{ cat.name }}
                            </h3>
                            <span class="text-[10px] text-zinc-400">
                                {{ cat.is_default ? 'Kategori Bawaan' : 'Kategori Kustom Pasangan' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-if="!cat.is_default">
                            <button
                                type="button"
                                @click="openEditModal(cat)"
                                class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 transition-colors"
                                title="Edit Kategori"
                            >
                                <Edit2 class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                @click="deleteCategory(cat)"
                                class="rounded-lg p-1 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 transition-colors"
                                title="Hapus Kategori"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </template>
                        <Lock v-else class="h-3.5 w-3.5 text-zinc-300 dark:text-zinc-600" title="Kategori Sistem Default" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="isCreateModalOpen"
            @click.self="isCreateModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm cursor-pointer"
        >
            <div
                @click.stop
                class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 cursor-default"
            >
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Tambah Kategori Baru</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Kategori</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Skincare Ayang, Tiket Konser, Kencan Bioskop"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Tipe Kategori</label>
                        <select
                            v-model="createForm.type"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Warna Kategori</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                @click="createForm.color = c"
                                class="flex h-7 w-7 items-center justify-center rounded-full transition-transform active:scale-95"
                                :style="{ backgroundColor: c }"
                            >
                                <Check v-if="createForm.color === c" class="h-3.5 w-3.5 text-white" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="w-full rounded-2xl bg-indigo-600 py-3 text-xs font-bold text-white shadow-md hover:bg-indigo-500 transition-all"
                    >
                        Simpan Kategori
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="isEditModalOpen && editingCategory"
            @click.self="isEditModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm cursor-pointer"
        >
            <div
                @click.stop
                class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 cursor-default"
            >
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Edit Kategori</h2>
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
                        <label class="block text-xs font-medium text-zinc-500">Nama Kategori</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Tipe Kategori</label>
                        <select
                            v-model="editForm.type"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        >
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500">Warna Kategori</label>
                        <div class="mt-2 flex flex-wrap gap-2">
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
