<script>
(() => {
    const targetId = @js($this->getId());
    const storageKey = @js($this->getLocalDraftStorageKey());

    if (!targetId || !storageKey) {
        return;
    }

    window.__cotizacionesAutosaveInstances = window.__cotizacionesAutosaveInstances || {};
    if (window.__cotizacionesAutosaveInstances[targetId]) {
        return;
    }
    window.__cotizacionesAutosaveInstances[targetId] = true;

    const dispatchToLivewire = (eventName, payload = {}) => {
        if (!window.Livewire || typeof window.Livewire.dispatch !== 'function') {
            return;
        }

        window.Livewire.dispatch(eventName, payload);
    };

    const persistLocalDraft = (payload, savedAt = null) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                payload,
                savedAt: savedAt || new Date().toISOString(),
            }));
        } catch (_) {
            // no-op
        }
    };

    const clearLocalDraft = () => {
        try {
            localStorage.removeItem(storageKey);
        } catch (_) {
            // no-op
        }
    };

    const restoreLocalDraft = () => {
        let parsed;

        try {
            parsed = JSON.parse(localStorage.getItem(storageKey) || 'null');
        } catch (_) {
            parsed = null;
        }

        if (!parsed || typeof parsed !== 'object' || !parsed.payload) {
            return;
        }

        dispatchToLivewire('cotizaciones-restore-local-draft', {
            targetId,
            payload: parsed.payload,
            savedAt: parsed.savedAt || null,
        });
    };

    const onStore = (event) => {
        const detail = event.detail || {};
        if (detail.targetId !== targetId) {
            return;
        }

        persistLocalDraft(detail.payload || {}, detail.savedAt || null);
    };

    const onClear = (event) => {
        const detail = event.detail || {};
        if (detail.targetId !== targetId) {
            return;
        }

        clearLocalDraft();
    };

    const onRequest = (event) => {
        const detail = event.detail || {};
        if (detail.targetId !== targetId) {
            return;
        }

        restoreLocalDraft();
    };

    const triggerAutosave = () => {
        dispatchToLivewire('cotizaciones-force-autosave', { targetId });
    };

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
    const onSubmitGuard = (event) => {
        const active = document.activeElement;
        if (!event.submitter && active instanceof HTMLElement && getSearchContainer(active)) {
            event.preventDefault();
        }
    };

    window.addEventListener('cotizaciones-local-draft-store', onStore);
    window.addEventListener('cotizaciones-local-draft-clear', onClear);
    window.addEventListener('cotizaciones-local-draft-request', onRequest);
    window.addEventListener('beforeunload', triggerAutosave);
    document.addEventListener('keydown', onSearchEnter, true);
    formEl?.addEventListener('submit', onSubmitGuard, true);

    const intervalId = window.setInterval(triggerAutosave, 15000);

    window.addEventListener('livewire:navigating', () => {
        window.clearInterval(intervalId);
        window.removeEventListener('cotizaciones-local-draft-store', onStore);
        window.removeEventListener('cotizaciones-local-draft-clear', onClear);
        window.removeEventListener('cotizaciones-local-draft-request', onRequest);
        window.removeEventListener('beforeunload', triggerAutosave);
        document.removeEventListener('keydown', onSearchEnter, true);
        formEl?.removeEventListener('submit', onSubmitGuard, true);
        delete window.__cotizacionesAutosaveInstances[targetId];
    }, { once: true });

    // Fallback: intentar restaurar desde local por si el request inicial no llego al listener.
    window.setTimeout(restoreLocalDraft, 400);
})();
</script>
