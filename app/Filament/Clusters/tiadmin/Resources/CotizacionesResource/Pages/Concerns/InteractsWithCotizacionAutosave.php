<?php

namespace App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages\Concerns;

use App\Models\CotizacionDraft;
use App\Services\Cotizaciones\CotizacionDraftService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

trait InteractsWithCotizacionAutosave
{
    protected bool $autosaveRecoveryAttempted = false;
    protected bool $autosaveRecoveredFromSource = false;
    protected bool $isApplyingAutosavePayload = false;
    protected ?string $autosaveLastHash = null;

    abstract protected function getAutosaveRecordId(): ?int;

    protected function afterFill(): void
    {
        $this->restoreAutosaveDraft();
    }

    public function updatedInteractsWithForms(string $statePath): void
    {
        parent::updatedInteractsWithForms($statePath);

        if ($this->isApplyingAutosavePayload) {
            return;
        }

        if (! str_starts_with($statePath, 'data')) {
            return;
        }

        $this->persistAutosaveDraft();
    }

    #[On('cotizaciones-force-autosave')]
    public function handleAutosaveTick(?string $targetId = null): void
    {
        if ($targetId && $targetId !== $this->getId()) {
            return;
        }

        $this->persistAutosaveDraft();
    }

    #[On('cotizaciones-restore-local-draft')]
    public function restoreLocalAutosaveDraft(array $payload = [], ?string $targetId = null, ?string $savedAt = null): void
    {
        if ($targetId && $targetId !== $this->getId()) {
            return;
        }

        if ($this->autosaveRecoveredFromSource || empty($payload)) {
            return;
        }

        $teamId = $this->getAutosaveTeamId();
        $userId = $this->getAutosaveUserId();

        if (! $teamId || ! $userId) {
            return;
        }

        $draft = $this->getCotizacionDraftService()->getDraft($teamId, $userId, $this->getAutosaveRecordId());
        if ($draft && $this->shouldApplyServerDraft($draft)) {
            return;
        }

        if (! $this->shouldApplyLocalDraft($savedAt)) {
            return;
        }

        $this->applyAutosavePayload($payload);
        $this->autosaveRecoveredFromSource = true;
        $this->persistAutosaveDraft();

        Notification::make()
            ->title('Borrador local restaurado')
            ->body('Se restauraron los datos guardados en este navegador.')
            ->info()
            ->send();
    }

    protected function clearAutosaveDrafts(): void
    {
        $teamId = $this->getAutosaveTeamId();
        $userId = $this->getAutosaveUserId();

        if ($teamId && $userId) {
            $this->getCotizacionDraftService()->deleteDraft($teamId, $userId, $this->getAutosaveRecordId());
        }

        $this->autosaveLastHash = null;

        $this->dispatch('cotizaciones-local-draft-clear',
            targetId: $this->getId(),
            key: $this->getLocalDraftStorageKey(),
        );
    }

    public function getLocalDraftStorageKey(): string
    {
        $teamId = $this->getAutosaveTeamId() ?? 0;
        $userId = $this->getAutosaveUserId() ?? 0;
        $recordId = $this->getAutosaveRecordId();

        if ($recordId) {
            return "cotizaciones-autosave:{$teamId}:{$userId}:edit:{$recordId}";
        }

        return "cotizaciones-autosave:{$teamId}:{$userId}:create";
    }

    protected function persistAutosaveDraft(): void
    {
        $teamId = $this->getAutosaveTeamId();
        $userId = $this->getAutosaveUserId();

        if (! $teamId || ! $userId) {
            return;
        }

        $payload = is_array($this->data) ? $this->data : [];
        $payloadHash = $this->getCotizacionDraftService()->calculatePayloadHash($payload);
        if ($this->autosaveLastHash === $payloadHash) {
            return;
        }

        $draft = $this->getCotizacionDraftService()->saveDraft(
            $teamId,
            $userId,
            $this->getAutosaveRecordId(),
            $payload,
            $payloadHash,
        );

        $this->autosaveLastHash = $payloadHash;

        $this->dispatch('cotizaciones-local-draft-store',
            targetId: $this->getId(),
            key: $this->getLocalDraftStorageKey(),
            payload: $draft->payload ?? [],
            savedAt: optional($draft->saved_at)->toIso8601String(),
        );
    }

    protected function restoreAutosaveDraft(): void
    {
        if ($this->autosaveRecoveryAttempted) {
            return;
        }

        $this->autosaveRecoveryAttempted = true;

        $teamId = $this->getAutosaveTeamId();
        $userId = $this->getAutosaveUserId();

        if (! $teamId || ! $userId) {
            return;
        }

        $draft = $this->getCotizacionDraftService()->getDraft($teamId, $userId, $this->getAutosaveRecordId());

        if ($draft && $this->shouldApplyServerDraft($draft) && is_array($draft->payload)) {
            $this->applyAutosavePayload($draft->payload);
            $this->autosaveRecoveredFromSource = true;
            $this->autosaveLastHash = $draft->payload_hash;

            Notification::make()
                ->title('Borrador automático restaurado')
                ->body('Se recuperó la última captura guardada automáticamente.')
                ->info()
                ->send();

            $this->dispatch('cotizaciones-local-draft-store',
                targetId: $this->getId(),
                key: $this->getLocalDraftStorageKey(),
                payload: $draft->payload,
                savedAt: optional($draft->saved_at)->toIso8601String(),
            );

            return;
        }

        $this->dispatch('cotizaciones-local-draft-request',
            targetId: $this->getId(),
            key: $this->getLocalDraftStorageKey(),
        );
    }

    protected function shouldApplyServerDraft(CotizacionDraft $draft): bool
    {
        $record = method_exists($this, 'getRecord') ? $this->getRecord() : null;

        if (! $record || ! $this->getAutosaveRecordId()) {
            return true;
        }

        if (! $record->updated_at || ! $draft->saved_at) {
            return true;
        }

        return $draft->saved_at->greaterThan($record->updated_at);
    }

    protected function shouldApplyLocalDraft(?string $savedAt): bool
    {
        $record = method_exists($this, 'getRecord') ? $this->getRecord() : null;

        if (! $record || ! $this->getAutosaveRecordId()) {
            return true;
        }

        if (! $record->updated_at || ! $savedAt) {
            return true;
        }

        try {
            $localSavedAt = Carbon::parse($savedAt);
        } catch (\Throwable) {
            return true;
        }

        return $localSavedAt->greaterThan($record->updated_at);
    }

    protected function applyAutosavePayload(array $payload): void
    {
        $this->isApplyingAutosavePayload = true;

        try {
            $this->form->fill($payload);
        } finally {
            $this->isApplyingAutosavePayload = false;
        }
    }

    protected function getCotizacionDraftService(): CotizacionDraftService
    {
        return app(CotizacionDraftService::class);
    }

    protected function getAutosaveTeamId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    protected function getAutosaveUserId(): ?int
    {
        return Filament::auth()?->id();
    }
}
