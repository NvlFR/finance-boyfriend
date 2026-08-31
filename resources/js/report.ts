const printButton = document.querySelector<HTMLButtonElement>(
    '[data-print-report]',
);

printButton?.addEventListener('click', () => window.print());

if (new URLSearchParams(window.location.search).has('print')) {
    window.addEventListener('load', () => window.print(), { once: true });
}
