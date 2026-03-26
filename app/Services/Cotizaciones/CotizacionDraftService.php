<?php

namespace App\Services\Cotizaciones;

use App\Models\CotizacionDraft;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CotizacionDraftService
{
    public function makeDraftKey(int $teamId, int $userId, ?int $cotizacionId = null): string
    {
        if ($cotizacionId) {
            return "cotizaciones:edit:{$teamId}:{$userId}:{$cotizacionId}";
        }

        return "cotizaciones:create:{$teamId}:{$userId}";
    }

    public function getDraft(int $teamId, int $userId, ?int $cotizacionId = null): ?CotizacionDraft
    {
        return CotizacionDraft::query()
            ->where('draft_key', $this->makeDraftKey($teamId, $userId, $cotizacionId))
            ->first();
    }

    public function saveDraft(
        int $teamId,
        int $userId,
        ?int $cotizacionId,
        array $payload,
        ?string $payloadHash = null,
    ): CotizacionDraft {
        $cleanPayload = $this->normalizeArray($payload);
        $hash = $payloadHash ?: $this->calculatePayloadHash($cleanPayload);

        return CotizacionDraft::query()->updateOrCreate(
            ['draft_key' => $this->makeDraftKey($teamId, $userId, $cotizacionId)],
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'cotizacion_id' => $cotizacionId,
                'payload' => $cleanPayload,
                'payload_hash' => $hash,
                'saved_at' => now(),
            ],
        );
    }

    public function deleteDraft(int $teamId, int $userId, ?int $cotizacionId = null): void
    {
        CotizacionDraft::query()
            ->where('draft_key', $this->makeDraftKey($teamId, $userId, $cotizacionId))
            ->delete();
    }

    public function calculatePayloadHash(array $payload): string
    {
        return hash('sha256', json_encode($this->normalizeArray($payload), JSON_UNESCAPED_UNICODE));
    }

    private function normalizeArray(array $payload): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->normalizeArray($value);
                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $result[$key] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if ($value instanceof Model) {
                $result[$key] = $value->getKey();
                continue;
            }

            if (is_object($value)) {
                $result[$key] = method_exists($value, '__toString') ? (string) $value : null;
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
