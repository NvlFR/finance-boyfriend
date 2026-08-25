import { ref } from 'vue';
import type { TransactionDefaults } from '@/types/finance';

const isTransactionModalOpen = ref(false);
const transactionModalDefaults = ref<TransactionDefaults>({});

export function useTransactionModal() {
    function openModal() {
        transactionModalDefaults.value = {};
        isTransactionModalOpen.value = true;
    }

    function openModalWithDefaults(defaults: TransactionDefaults) {
        transactionModalDefaults.value = defaults;
        isTransactionModalOpen.value = true;
    }

    function closeModal() {
        isTransactionModalOpen.value = false;
    }

    function toggleModal() {
        isTransactionModalOpen.value = !isTransactionModalOpen.value;
    }

    return {
        isOpen: isTransactionModalOpen,
        defaults: transactionModalDefaults,
        openModal,
        openModalWithDefaults,
        closeModal,
        toggleModal,
    };
}
