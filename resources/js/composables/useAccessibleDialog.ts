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
    let previousRootOverflow = '';
    let previousRootOverscrollBehavior = '';

    function unlockPageScroll(): void {
        document.body.style.overflow = previousBodyOverflow;
        document.documentElement.style.overflow = previousRootOverflow;
        document.documentElement.style.overscrollBehavior =
            previousRootOverscrollBehavior;
    }

    watch(isOpen, async (open) => {
        if (open) {
            previouslyFocusedElement = document.activeElement as HTMLElement;
            previousBodyOverflow = document.body.style.overflow;
            previousRootOverflow = document.documentElement.style.overflow;
            previousRootOverscrollBehavior =
                document.documentElement.style.overscrollBehavior;
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.documentElement.style.overscrollBehavior = 'none';
            await nextTick();
            dialogRef.value?.focus();

            return;
        }

        unlockPageScroll();
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
        unlockPageScroll();
    });

    return { dialogRef, handleDialogKeydown };
}
