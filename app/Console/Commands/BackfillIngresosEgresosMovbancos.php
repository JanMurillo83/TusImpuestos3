<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillIngresosEgresosMovbancos extends Command
{
    protected $signature = 'app:backfill-ingeg-movbancos
        {--team_id= : Limitar el backfill a un team_id}
        {--only-unlinked : Procesar solo movimientos sin registros en ingresos_egresos_movbancos}
        {--dry-run : Simular el backfill sin insertar registros}';

    protected $description = 'Backfill historico de relaciones movimiento bancario <-> ingresos_egresos';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyUnlinked = (bool) $this->option('only-unlinked');
        $teamIdOption = $this->option('team_id');
        $teamId = is_numeric($teamIdOption) ? intval($teamIdOption) : null;

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se insertaran registros');
        }

        $query = DB::table('movbancos')
            ->select('id', 'team_id', 'uuid', 'factura');

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
            $this->info("Filtrando por team_id={$teamId}");
        }

        if ($onlyUnlinked) {
            $query->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('ingresos_egresos_movbancos as iem')
                    ->whereColumn('iem.movbancos_id', 'movbancos.id');
            });
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No hay movimientos a procesar');
            return 0;
        }

        $this->info("Movimientos a procesar: {$total}");

        $stats = [
            'processed' => 0,
            'with_candidates' => 0,
            'without_candidates' => 0,
            'inserted' => 0,
            'already_linked' => 0,
            'would_insert' => 0,
        ];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById(300, function ($movimientos) use (&$stats, $dryRun, $bar): void {
            foreach ($movimientos as $movimiento) {
                $stats['processed']++;
                $movimientoId = intval($movimiento->id);
                $teamIdMov = is_numeric($movimiento->team_id) ? intval($movimiento->team_id) : null;

                $candidatos = $this->obtenerIngresosEgresosCandidatos(
                    $movimientoId,
                    $teamIdMov,
                    (string) ($movimiento->uuid ?? ''),
                    (string) ($movimiento->factura ?? '')
                );

                if (count($candidatos) === 0) {
                    $stats['without_candidates']++;
                    $bar->advance();
                    continue;
                }

                $stats['with_candidates']++;

                foreach ($candidatos as $ingresoEgresoId) {
                    $exists = DB::table('ingresos_egresos_movbancos')
                        ->where('movbancos_id', $movimientoId)
                        ->where('ingresos_egresos_id', $ingresoEgresoId)
                        ->exists();

                    if ($exists) {
                        $stats['already_linked']++;
                        continue;
                    }

                    if ($dryRun) {
                        $stats['would_insert']++;
                        continue;
                    }

                    DB::table('ingresos_egresos_movbancos')->insert([
                        'movbancos_id' => $movimientoId,
                        'ingresos_egresos_id' => $ingresoEgresoId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stats['inserted']++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metrica', 'Valor'], [
            ['Movimientos procesados', $stats['processed']],
            ['Con candidatos', $stats['with_candidates']],
            ['Sin candidatos', $stats['without_candidates']],
            ['Ya vinculados', $stats['already_linked']],
            [$dryRun ? 'Se insertarian' : 'Insertados', $dryRun ? $stats['would_insert'] : $stats['inserted']],
        ]);

        if ($dryRun) {
            $this->info('DRY-RUN completado. Ejecuta sin --dry-run para aplicar cambios.');
        } else {
            $this->info('Backfill completado.');
        }

        return 0;
    }

    private function obtenerIngresosEgresosCandidatos(
        int $movimientoId,
        ?int $teamId,
        string $uuid,
        string $facturaRaw
    ): array {
        $ids = [];

        $ids = array_merge($ids, $this->candidatosDesdePolizaAuxiliar($movimientoId, $teamId));
        $ids = array_merge($ids, $this->candidatosDesdeUuid($uuid, $teamId));

        $referencias = $this->extraerReferencias($facturaRaw);
        if (count($referencias) > 0) {
            $ids = array_merge($ids, $this->candidatosDesdeReferencia($referencias, $teamId));
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, fn (int $id) => $id > 0));

        return $ids;
    }

    private function candidatosDesdePolizaAuxiliar(int $movimientoId, ?int $teamId): array
    {
        $query = DB::table('cat_polizas as cp')
            ->join('auxiliares as aux', 'aux.cat_polizas_id', '=', 'cp.id')
            ->join('ingresos_egresos as ie', 'ie.id', '=', 'aux.igeg_id')
            ->where('cp.idmovb', $movimientoId)
            ->whereNotNull('aux.igeg_id');

        if ($teamId !== null) {
            $query->where('cp.team_id', $teamId)
                ->where('aux.team_id', $teamId)
                ->where('ie.team_id', $teamId);
        }

        return $query->distinct()->pluck('aux.igeg_id')->map(fn ($id) => intval($id))->all();
    }

    private function candidatosDesdeUuid(string $uuid, ?int $teamId): array
    {
        $uuid = trim($uuid);
        if ($this->esReferenciaInvalida($uuid)) {
            return [];
        }

        $cfdiQuery = DB::table('almacencfdis')
            ->select('id')
            ->where('UUID', $uuid);

        if ($teamId !== null) {
            $cfdiQuery->where('team_id', $teamId);
        }

        $xmlIds = $cfdiQuery->pluck('id')->map(fn ($id) => intval($id))->all();
        if (count($xmlIds) === 0) {
            return [];
        }

        $ingegQuery = DB::table('ingresos_egresos')
            ->select('id')
            ->whereIn('xml_id', $xmlIds);

        if ($teamId !== null) {
            $ingegQuery->where('team_id', $teamId);
        }

        return $ingegQuery->pluck('id')->map(fn ($id) => intval($id))->all();
    }

    private function candidatosDesdeReferencia(array $referencias, ?int $teamId): array
    {
        if (count($referencias) === 0) {
            return [];
        }

        $ids = [];

        $referenciaQuery = DB::table('ingresos_egresos')
            ->select('id')
            ->whereIn('referencia', $referencias);

        if ($teamId !== null) {
            $referenciaQuery->where('team_id', $teamId);
        }

        $ids = array_merge($ids, $referenciaQuery->pluck('id')->map(fn ($id) => intval($id))->all());

        $cfdiQuery = DB::table('ingresos_egresos as ie')
            ->join('almacencfdis as cfdi', 'cfdi.id', '=', 'ie.xml_id')
            ->select('ie.id')
            ->where(function ($query) use ($referencias): void {
                foreach ($referencias as $referencia) {
                    $query->orWhereRaw("CONCAT(COALESCE(cfdi.Serie,''),COALESCE(cfdi.Folio,'')) = ?", [$referencia]);
                }
            });

        if ($teamId !== null) {
            $cfdiQuery->where('ie.team_id', $teamId)
                ->where('cfdi.team_id', $teamId);
        }

        $ids = array_merge($ids, $cfdiQuery->distinct()->pluck('ie.id')->map(fn ($id) => intval($id))->all());

        return $ids;
    }

    private function extraerReferencias(string $facturaRaw): array
    {
        $facturaRaw = trim($facturaRaw);
        if ($this->esReferenciaInvalida($facturaRaw)) {
            return [];
        }

        $tokens = preg_split('/[\s,;|]+/', $facturaRaw) ?: [];
        $tokens = array_map('trim', $tokens);
        $tokens = array_filter($tokens, fn (string $token) => ! $this->esReferenciaInvalida($token));

        return array_values(array_unique($tokens));
    }

    private function esReferenciaInvalida(string $value): bool
    {
        $value = strtoupper(trim($value));
        return $value === '' || in_array($value, ['N/A', 'N/I', 'S/F', 'PRESTAMO'], true);
    }
}

