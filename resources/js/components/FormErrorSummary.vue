<script setup lang="ts">
import { AlertCircle } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        errors?: Record<string, string>;
        title?: string;
    }>(),
    {
        errors: () => ({}),
        title: 'Periksa kembali data yang kamu isi.',
    },
);

const messages = computed(() => [...new Set(Object.values(props.errors))]);
</script>

<template>
    <div
        v-if="messages.length"
        role="alert"
        aria-live="assertive"
        class="flex gap-2 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/40 dark:text-rose-300"
    >
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
        <div>
            <p class="text-xs font-bold">{{ title }}</p>
            <ul class="mt-1 list-inside list-disc text-xs">
                <li v-for="message in messages" :key="message">
                    {{ message }}
                </li>
            </ul>
        </div>
    </div>
</template>
