<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        allowDecimals?: boolean;
        decimalScale?: number;
    }>(),
    {
        modelValue: '',
        allowDecimals: false,
        decimalScale: 2,
    },
);

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void;
}>();

function groupThousands(value: string): string {
    const normalized = value.replace(/^0+(?=\d)/, '') || '0';

    return normalized.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function formatModelValue(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const normalized = String(value).replace(',', '.');
    const [integerPart, fractionPart] = normalized.split('.', 2);
    const integerDigits = integerPart.replace(/\D/g, '') || '0';
    const formattedInteger = groupThousands(integerDigits);

    if (!props.allowDecimals || fractionPart === undefined) {
        return formattedInteger;
    }

    return `${formattedInteger},${fractionPart.replace(/\D/g, '').slice(0, props.decimalScale)}`;
}

const displayValue = computed(() => formatModelValue(props.modelValue));

function handleInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const decimalSeparatorIndex = props.allowDecimals
        ? input.value.lastIndexOf(',')
        : -1;
    const integerSource =
        decimalSeparatorIndex >= 0
            ? input.value.slice(0, decimalSeparatorIndex)
            : input.value;
    const integerDigits = integerSource.replace(/\D/g, '');

    if (integerDigits === '') {
        input.value = '';
        emit('update:modelValue', '');

        return;
    }

    const normalizedInteger = integerDigits.replace(/^0+(?=\d)/, '');
    let rawValue = normalizedInteger;
    let formattedValue = groupThousands(normalizedInteger);

    if (decimalSeparatorIndex >= 0) {
        const fractionDigits = input.value
            .slice(decimalSeparatorIndex + 1)
            .replace(/\D/g, '')
            .slice(0, props.decimalScale);
        rawValue += `.${fractionDigits}`;
        formattedValue += `,${fractionDigits}`;
    }

    input.value = formattedValue;
    emit('update:modelValue', rawValue);
}
</script>

<template>
    <input
        :value="displayValue"
        type="text"
        :inputmode="allowDecimals ? 'decimal' : 'numeric'"
        autocomplete="off"
        @input="handleInput"
    />
</template>
