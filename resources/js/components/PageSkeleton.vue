<script setup lang="ts">
defineProps<{
    type?: 'cards' | 'list' | 'dashboard' | 'detail';
}>();
</script>

<template>
    <div class="space-y-5 animate-pulse">
        <!-- Hero Skeleton Card -->
        <div class="h-44 w-full rounded-3xl bg-zinc-200/80 dark:bg-zinc-800/80" />

        <!-- 4 Action Pills Skeleton -->
        <div class="grid grid-cols-4 gap-2">
            <div v-for="i in 4" :key="i" class="h-16 rounded-2xl bg-zinc-200/70 dark:bg-zinc-800/70" />
        </div>

        <!-- Section Title Skeleton -->
        <div class="flex justify-between items-center pt-2">
            <div class="h-5 w-32 rounded-lg bg-zinc-200 dark:bg-zinc-800" />
            <div class="h-4 w-16 rounded-lg bg-zinc-200/60 dark:bg-zinc-800/60" />
        </div>

        <!-- 3 Cards / Items Skeleton -->
        <div class="space-y-3">
            <div
                v-for="i in 3"
                :key="i"
                class="h-20 w-full rounded-2xl bg-zinc-200/70 dark:bg-zinc-800/70"
            />
        </div>
    </div>
</template>
