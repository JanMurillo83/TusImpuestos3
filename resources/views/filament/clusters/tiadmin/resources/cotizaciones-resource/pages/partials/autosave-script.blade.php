<script>
(() => {
    const targetId = @js($this->getId());
    if (!targetId) {
        return;
    }

    window.__cotizacionesSearchGuardInstances = window.__cotizacionesSearchGuardInstances || {};
    if (window.__cotizacionesSearchGuardInstances[targetId]) {
        return;
    }
    window.__cotizacionesSearchGuardInstances[targetId] = true;

    const getSearchContainer = (element) => {
        if (!(element instanceof HTMLElement)) {
            return null;
        }

        return (
            element.closest('.choices') ||
            element.closest('.ts-wrapper') ||
            element.closest('.ts-control') ||
            element.closest('.fi-fo-select')
        );
    };

    const hasNoSearchResults = (element) => {
        const container = getSearchContainer(element);
        if (!container) {
            return false;
        }

        const choicesResults = container.querySelectorAll('.choices__list--dropdown .choices__item--choice:not(.choices__placeholder):not(.is-disabled)');
        if (choicesResults.length > 0) {
            return false;
        }

        const tomSelectResults = container.querySelectorAll('.ts-dropdown .option:not(.disabled)');
        if (tomSelectResults.length > 0) {
            return false;
        }

        const hasNoResultsLabel = container.querySelector('.choices__item--no-results, .ts-dropdown .no-results');

        return Boolean(hasNoResultsLabel || (choicesResults.length === 0 && tomSelectResults.length === 0));
    };

    const onSearchEnter = (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (!getSearchContainer(target)) {
            return;
        }

        if (!hasNoSearchResults(target)) {
            return;
        }

        // Evita submit accidental del formulario cuando no hay coincidencias.
        event.preventDefault();
        event.stopPropagation();
    };

    const formEl = document.getElementById('form');
    let isDirty = false;

    const onDirty = () => {
        isDirty = true;
    };

    const onBeforeUnload = (event) => {
        if (!isDirty) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    };

    const onFormSubmit = () => {
        isDirty = false;
    };

    const onSubmitGuard = (event) => {
        const active = document.activeElement;
        if (!event.submitter && active instanceof HTMLElement && getSearchContainer(active)) {
            event.preventDefault();
        }
    };

    document.addEventListener('keydown', onSearchEnter, true);
    window.addEventListener('beforeunload', onBeforeUnload);
    formEl?.addEventListener('input', onDirty, true);
    formEl?.addEventListener('change', onDirty, true);
    formEl?.addEventListener('submit', onSubmitGuard, true);
    formEl?.addEventListener('submit', onFormSubmit, true);

    window.addEventListener('livewire:navigating', () => {
        document.removeEventListener('keydown', onSearchEnter, true);
        window.removeEventListener('beforeunload', onBeforeUnload);
        formEl?.removeEventListener('input', onDirty, true);
        formEl?.removeEventListener('change', onDirty, true);
        formEl?.removeEventListener('submit', onSubmitGuard, true);
        formEl?.removeEventListener('submit', onFormSubmit, true);
        delete window.__cotizacionesSearchGuardInstances[targetId];
    }, { once: true });
})();
</script>
