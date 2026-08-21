<script setup lang="ts">
import { ref, computed } from 'vue';
import { TrendingUp, TrendingDown, Calendar, Sparkles } from '@lucide/vue';

interface DailyTrend {
    date: string;
    day: string;
    expense: number;
    income: number;
}

const props = defineProps<{
    data: DailyTrend[];
}>();

const hoveredIndex = ref<number | null>(null);

const maxVal = computed(() => {
    let max = 100000;
    props.data.forEach((d) => {
        if (d.expense > max) max = d.expense;
        if (d.income > max) max = d.income;
    });
    return max;
});

const totalExpense = computed(() => props.data.reduce((sum, d) => sum + d.expense, 0));
const totalIncome = computed(() => props.data.reduce((sum, d) => sum + d.income, 0));

function getBarHeight(val: number): number {
    if (maxVal.value === 0) return 4;
    return Math.max(4, Math.round((val / maxVal.value) * 120));
}
</script>

<template>
    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                    <TrendingUp class="h-4 w-4" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Tren Cashflow 7 Hari</h3>
                    <p class="text-[11px] text-zinc-500">Pemasukan vs Pengeluaran Harian</p>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-3 text-[11px] font-semibold">
                <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                    <span>Masuk</span>
                </div>
                <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500" />
                    <span>Keluar</span>
                </div>
            </div>
        </div>

        <!-- 7-Day Interactive Bar Chart -->
        <div class="pt-2">
            <div class="flex items-end justify-between gap-2 h-36 px-1 border-b border-zinc-100 dark:border-zinc-800 pb-2">
                <div
                    v-for="(item, index) in data"
                    :key="item.date"
                    class="relative flex-1 flex flex-col items-center justify-end h-full group cursor-pointer"
                    @mouseenter="hoveredIndex = index"
                    @mouseleave="hoveredIndex = null"
                >
                    <!-- Tooltip on Hover -->
                    <div
                        v-if="hoveredIndex === index"
                        class="absolute bottom-full mb-2 z-20 flex flex-col items-center rounded-xl bg-zinc-900 px-2.5 py-1.5 text-[10px] text-white shadow-xl pointer-events-none whitespace-nowrap dark:bg-zinc-800 border border-zinc-700"
                    >
                        <span class="font-bold text-zinc-300">{{ item.day }}, {{ item.date }}</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-emerald-400">+Rp {{ item.income.toLocaleString('id-ID') }}</span>
                            <span class="text-rose-400">-Rp {{ item.expense.toLocaleString('id-ID') }}</span>
                        </div>
                    </div>

                    <!-- Dual Bars -->
                    <div class="flex items-end gap-1 w-full max-w-[28px] justify-center">
                        <!-- Income Bar -->
                        <div
                            class="w-full rounded-t-md bg-emerald-500 transition-all duration-300 group-hover:bg-emerald-400 group-hover:shadow-md group-hover:shadow-emerald-500/30"
                            :style="{ height: `${getBarHeight(item.income)}px` }"
                        />
                        <!-- Expense Bar -->
                        <div
                            class="w-full rounded-t-md bg-rose-500 transition-all duration-300 group-hover:bg-rose-400 group-hover:shadow-md group-hover:shadow-rose-500/30"
                            :style="{ height: `${getBarHeight(item.expense)}px` }"
                        />
                    </div>
                </div>
            </div>

            <!-- Date Labels -->
            <div class="flex justify-between gap-2 pt-2 px-1 text-[11px] font-medium text-zinc-400">
                <div
                    v-for="(item, index) in data"
                    :key="item.date"
                    class="flex-1 text-center"
                    :class="{ 'font-bold text-indigo-600 dark:text-indigo-400': hoveredIndex === index }"
                >
                    <div>{{ item.day }}</div>
                    <div class="text-[9px] text-zinc-400">{{ item.date.split(' ')[0] }}</div>
                </div>
            </div>
        </div>

        <!-- 7-Day Totals Footer -->
        <div class="grid grid-cols-2 gap-2 rounded-2xl bg-zinc-50/80 p-3 dark:bg-zinc-800/40 text-xs border border-zinc-100 dark:border-zinc-800/80">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <TrendingUp class="h-3.5 w-3.5" />
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400">Pemasukan 7 Hari</span>
                    <p class="font-extrabold text-emerald-600 dark:text-emerald-400">
                        +Rp {{ totalIncome.toLocaleString('id-ID') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                    <TrendingDown class="h-3.5 w-3.5" />
                </div>
                <div>
                    <span class="text-[10px] text-zinc-400">Pengeluaran 7 Hari</span>
                    <p class="font-extrabold text-rose-600 dark:text-rose-400">
                        -Rp {{ totalExpense.toLocaleString('id-ID') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
