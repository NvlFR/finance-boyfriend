import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import type { WatchSource } from 'vue';

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export function useAccessibleDialog(
    isOpen: WatchSource<boolean>,
    closeDialog: () => void,
) {
    const dialogRef = ref<HTMLElement | null>(null);
    let previouslyFocusedElement: HTMLElement | null = null;
    let previousBodyOverflow = '';

    watch(isOpen, async (open) => {
        if (open) {
            previouslyFocusedElement = document.activeElement as HTMLElement;
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            await nextTick();
            dialogRef.value?.focus();

            return;
        }

        document.body.style.overflow = previousBodyOverflow;
        previouslyFocusedElement?.focus();
    });

    function handleDialogKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeDialog();

            return;
        }

        if (event.key !== 'Tab' || !dialogRef.value) {
            return;
        }

        const focusableElements = Array.from(
            dialogRef.value.querySelectorAll<HTMLElement>(focusableSelector),
        ).filter((element) => element.offsetParent !== null);

        if (focusableElements.length === 0) {
            event.preventDefault();

            return;
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }

    onBeforeUnmount(() => {
        document.body.style.overflow = previousBodyOverflow;
    });

    return { dialogRef, handleDialogKeydown };
}
