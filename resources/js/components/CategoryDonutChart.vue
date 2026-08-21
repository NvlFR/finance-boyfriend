<script setup lang="ts">
import { ref, computed } from 'vue';
import { PieChart, Tag, Users, User as UserIcon } from '@lucide/vue';

interface CategorySpending {
    id: number;
    name: string;
    color: string;
    total: number;
    percentage: number;
}

const props = defineProps<{
    categories: CategorySpending[];
    monthlySpending: number;
    spendingByScope?: {
        shared: number;
        personal: number;
    };
}>();

const hoveredCategory = ref<CategorySpending | null>(null);

// Calculate SVG donut stroke offsets
const strokeDashOffsetArray = computed(() => {
    let currentOffset = 0;
    const circumference = 2 * Math.PI * 40; // radius = 40, circ = 251.32

    return props.categories.map((cat) => {
        const strokeDasharray = `${(cat.percentage / 100) * circumference} ${circumference}`;
        const offset = -currentOffset;
        currentOffset += (cat.percentage / 100) * circumference;
        return {
            ...cat,
            strokeDasharray,
            offset,
        };
    });
});

const sharedPercentage = computed(() => {
    if (!props.spendingByScope || props.monthlySpending === 0) return 50;
    return Math.round((props.spendingByScope.shared / props.monthlySpending) * 100);
});

const personalPercentage = computed(() => {
    if (!props.spendingByScope || props.monthlySpending === 0) return 50;
    return 100 - sharedPercentage.value;
});
</script>

<template>
    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                    <PieChart class="h-4 w-4" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Kategori Pengeluaran Bulan Ini</h3>
                    <p class="text-[11px] text-zinc-500">Distribusi pengeluaran kencan & pribadi</p>
                </div>
            </div>

            <span class="text-xs font-extrabold text-zinc-900 dark:text-zinc-100">
                Rp {{ monthlySpending.toLocaleString('id-ID') }}
            </span>
        </div>

        <!-- Donut & Breakdown Area -->
        <div v-if="categories.length > 0" class="flex flex-col sm:flex-row items-center gap-5 pt-2">
            <!-- SVG Donut Chart -->
            <div class="relative flex items-center justify-center shrink-0">
                <svg class="h-36 w-36 -rotate-90 transform" viewBox="0 0 100 100">
                    <!-- Background Circle -->
                    <circle
                        cx="50"
                        cy="50"
                        r="40"
                        fill="transparent"
                        stroke="currentColor"
                        stroke-width="12"
                        class="text-zinc-100 dark:text-zinc-800"
                    />

                    <!-- Donut Segments -->
                    <circle
                        v-for="item in strokeDashOffsetArray"
                        :key="item.id"
                        cx="50"
                        cy="50"
                        r="40"
                        fill="transparent"
                        :stroke="item.color || '#6366F1'"
                        stroke-width="12"
                        :stroke-dasharray="item.strokeDasharray"
                        :stroke-dashoffset="item.offset"
                        class="transition-all duration-300 cursor-pointer hover:stroke-width-14"
                        @mouseenter="hoveredCategory = item"
                        @mouseleave="hoveredCategory = null"
                    />
                </svg>

                <!-- Center Text Inside Donut -->
                <div class="absolute flex flex-col items-center justify-center text-center pointer-events-none px-2">
                    <span class="text-[10px] text-zinc-400 font-medium">
                        {{ hoveredCategory ? hoveredCategory.name : 'Total' }}
                    </span>
                    <span class="text-xs font-black text-zinc-900 dark:text-zinc-100">
                        {{ hoveredCategory ? `${hoveredCategory.percentage}%` : `${categories.length} Kat` }}
                    </span>
                </div>
            </div>

            <!-- Category List Chips -->
            <div class="w-full space-y-2 max-h-44 overflow-y-auto pr-1">
                <div
                    v-for="cat in categories"
                    :key="cat.id"
                    @mouseenter="hoveredCategory = cat"
                    @mouseleave="hoveredCategory = null"
                    class="flex items-center justify-between rounded-2xl border p-2 text-xs transition-all cursor-pointer"
                    :class="[
                        hoveredCategory?.id === cat.id
                            ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30 ring-1 ring-indigo-500/20'
                            : 'border-zinc-100 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-800/30'
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="h-3 w-3 rounded-full shrink-0 shadow-xs"
                            :style="{ backgroundColor: cat.color || '#6366F1' }"
                        />
                        <span class="font-bold text-zinc-800 dark:text-zinc-200 truncate max-w-[120px]">
                            {{ cat.name }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-zinc-600 dark:text-zinc-300">
                            Rp {{ Number(cat.total).toLocaleString('id-ID') }}
                        </span>
                        <span class="rounded-md bg-zinc-200/60 px-1.5 py-0.5 text-[10px] font-bold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                            {{ cat.percentage }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="rounded-2xl border border-dashed border-zinc-200 p-6 text-center text-xs text-zinc-400 dark:border-zinc-800"
        >
            Belum ada data transaksi pengeluaran bulan ini. Catat transaksi kencan untuk melihat grafik!
        </div>

        <!-- Shared vs Personal Scope Progress Ratio -->
        <div v-if="spendingByScope && monthlySpending > 0" class="border-t border-zinc-100 dark:border-zinc-800 pt-3 space-y-2">
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400 font-bold">
                    <Users class="h-3.5 w-3.5" />
                    <span>Kencan Bersama ({{ sharedPercentage }}%)</span>
                </div>
                <div class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400 font-bold">
                    <UserIcon class="h-3.5 w-3.5" />
                    <span>Pribadi ({{ personalPercentage }}%)</span>
                </div>
            </div>

            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800 flex">
                <div
                    class="h-full bg-rose-500 transition-all duration-500"
                    :style="{ width: `${sharedPercentage}%` }"
                />
                <div
                    class="h-full bg-indigo-500 transition-all duration-500"
                    :style="{ width: `${personalPercentage}%` }"
                />
            </div>
        </div>
    </div>
</template>
