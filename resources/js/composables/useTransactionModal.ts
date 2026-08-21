import { ref } from 'vue';

const isTransactionModalOpen = ref(false);

export function useTransactionModal() {
    function openModal() {
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
        openModal,
        closeModal,
        toggleModal,
    };
}
