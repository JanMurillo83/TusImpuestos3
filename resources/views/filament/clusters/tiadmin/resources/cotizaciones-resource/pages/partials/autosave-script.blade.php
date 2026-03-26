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

    window.addEventListener('cotizaciones-local-draft-store', onStore);
    window.addEventListener('cotizaciones-local-draft-clear', onClear);
    window.addEventListener('cotizaciones-local-draft-request', onRequest);
    window.addEventListener('beforeunload', triggerAutosave);

    const intervalId = window.setInterval(triggerAutosave, 15000);

    window.addEventListener('livewire:navigating', () => {
        window.clearInterval(intervalId);
        window.removeEventListener('cotizaciones-local-draft-store', onStore);
        window.removeEventListener('cotizaciones-local-draft-clear', onClear);
        window.removeEventListener('cotizaciones-local-draft-request', onRequest);
        window.removeEventListener('beforeunload', triggerAutosave);
        delete window.__cotizacionesAutosaveInstances[targetId];
    }, { once: true });

    // Fallback: intentar restaurar desde local por si el request inicial no llego al listener.
    window.setTimeout(restoreLocalDraft, 400);
})();
</script>
