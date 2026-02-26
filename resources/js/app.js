import './bootstrap';

// Evita que Enter/Return grabe formularios y lo trata como Tab en inputs.
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') {
        return;
    }

    const target = event.target;
    if (!target || !(target instanceof HTMLElement)) {
        return;
    }

    const tag = target.tagName.toLowerCase();
    const type = target.getAttribute('type')?.toLowerCase() ?? '';

    // Permitir Enter en textarea y contenido editable.
    if (tag === 'textarea' || target.isContentEditable) {
        return;
    }

    // Evitar enviar formularios o activar botones con Enter.
    event.preventDefault();

    const root =
        target.closest('form') ||
        target.closest('.fi-modal') ||
        document.body;

    const focusableSelector = [
        'input:not([type="hidden"]):not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        'button:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    const focusables = Array.from(root.querySelectorAll(focusableSelector))
        .filter((el) => el instanceof HTMLElement && el.offsetParent !== null);

    const currentIndex = focusables.indexOf(target);
    if (currentIndex === -1) {
        return;
    }

    const next = focusables[currentIndex + 1] ?? focusables[0];
    next?.focus();
});
