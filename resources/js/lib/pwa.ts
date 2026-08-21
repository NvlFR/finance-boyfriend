import { ref } from 'vue';

export const isPwaInstallable = ref(false);
export const isPwaInstalled = ref(false);
export const deferredPrompt = ref<any>(null);

export function initializePwa() {
    // Check if running in standalone mode (already installed)
    if (
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as any).standalone === true
    ) {
        isPwaInstalled.value = true;
    }

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker
                .register('/sw.js')
                .then((registration) => {
                    console.log('PWA ServiceWorker registered with scope: ', registration.scope);
                })
                .catch((err) => {
                    console.warn('PWA ServiceWorker registration failed: ', err);
                });
        });
    }

    // Listen for beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e: Event) => {
        // Prevent default mini-infobar on mobile
        e.preventDefault();
        // Stash event so it can be triggered later
        deferredPrompt.value = e;
        isPwaInstallable.value = true;
    });

    // Listen for appinstalled event
    window.addEventListener('appinstalled', () => {
        isPwaInstalled.value = true;
        isPwaInstallable.value = false;
        deferredPrompt.value = null;
        console.log('Couple Finance PWA was successfully installed');
    });
}

export async function promptPwaInstall(): Promise<boolean> {
    if (!deferredPrompt.value) return false;

    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    deferredPrompt.value = null;
    isPwaInstallable.value = false;

    return outcome === 'accepted';
}
