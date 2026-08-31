import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const isLogoutModalOpen = ref(false);
const isLoggingOut = ref(false);

export function useLogoutModal() {
    function openLogoutModal() {
        isLogoutModalOpen.value = true;
    }

    function closeLogoutModal() {
        isLogoutModalOpen.value = false;
    }

    function confirmLogout() {
        isLoggingOut.value = true;
        router.post(
            '/logout',
            {},
            {
                onFinish: () => {
                    isLoggingOut.value = false;
                    isLogoutModalOpen.value = false;
                },
            },
        );
    }

    return {
        isLogoutModalOpen,
        isLoggingOut,
        openLogoutModal,
        closeLogoutModal,
        confirmLogout,
    };
}
