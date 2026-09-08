<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <header class="flex min-h-14 items-center justify-between gap-3 px-1">
        <div class="min-w-0">
            <h1
                class="truncate text-2xl font-black tracking-tight text-slate-950 dark:text-white"
            >
                {{ title }}
            </h1>
            <p
                v-if="subtitle"
                class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400"
            >
                {{ subtitle }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <slot />
        </div>
    </header>
</template>
