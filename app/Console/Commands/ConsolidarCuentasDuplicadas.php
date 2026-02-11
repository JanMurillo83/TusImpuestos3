<?php

namespace App\Console\Commands;

use App\Models\Auxiliares;
use App\Models\CatCuentas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidarCuentasDuplicadas extends Command
{
    protected $signature = 'cuentas:consolidar-duplicadas {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios} {--team-id= : Procesar solo un team_id específico}';

    protected $description = 'Consolida cuentas duplicadas (mismo código en mismo team_id) y actualiza referencias en auxiliares';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificTeamId = $this->option('team-id');

        $this->info('=== CONSOLIDACIÓN DE CUENTAS DUPLICADAS ===');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos');
            $this->newLine();
        }

        // Paso 1: Identificar duplicados
        $this->info('Paso 1: Identificando cuentas duplicadas...');

        $query = DB::table('cat_cuentas')
            ->select('team_id', 'codigo', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->groupBy('team_id', 'codigo')
            ->having('count', '>', 1);

        if ($specificTeamId) {
            $query->where('team_id', $specificTeamId);
        }

        $duplicados = $query->get();

        if ($duplicados->isEmpty()) {
            $this->info('✅ No se encontraron cuentas duplicadas.');
            return 0;
        }

        $this->warn("❌ Se encontraron {$duplicados->count()} grupos de cuentas duplicadas");
        $this->newLine();

        // Mostrar resumen de duplicados
        $table = [];
        foreach ($duplicados as $dup) {
            $ids = explode(',', $dup->ids);
            $cuenta = CatCuentas::find($ids[0]);
            $table[] = [
                'Team ID' => $dup->team_id,
                'Código' => $dup->codigo,
                'Nombre' => $cuenta ? $cuenta->nombre : 'N/A',
                'IDs Duplicados' => $dup->ids,
                'Total' => $dup->count
            ];
        }

        $this->table(['Team ID', 'Código', 'Nombre', 'IDs Duplicados', 'Total'], $table);
        $this->newLine();

        if (!$dryRun && !$this->confirm('¿Desea continuar con la consolidación?', false)) {
            $this->info('Operación cancelada.');
            return 0;
        }

        // Paso 2: Procesar cada grupo de duplicados
        $this->info('Paso 2: Procesando duplicados...');
        $this->newLine();

        $totalProcesados = 0;
        $totalAuxiliaresActualizados = 0;
        $totalCuentasEliminadas = 0;

        foreach ($duplicados as $dup) {
            $ids = explode(',', $dup->ids);

            // La cuenta a mantener será la primera (ID más antiguo)
            $idMantener = $ids[0];
            $idsEliminar = array_slice($ids, 1);

            $cuentaMantener = CatCuentas::find($idMantener);

            $this->line("Procesando: Team {$dup->team_id} - Código {$dup->codigo} ({$cuentaMantener->nombre})");
            $this->line("  → Manteniendo ID: {$idMantener}");
            $this->line("  → Eliminando IDs: " . implode(', ', $idsEliminar));

            // Contar auxiliares que apuntan a las cuentas a eliminar
            $auxiliaresCount = Auxiliares::where('team_id', $dup->team_id)
                ->where('codigo', $dup->codigo)
                ->count();

            $this->line("  → Auxiliares encontrados: {$auxiliaresCount}");

            if (!$dryRun) {
                DB::transaction(function() use ($dup, $idMantener, $idsEliminar, &$totalAuxiliaresActualizados, &$totalCuentasEliminadas) {
                    // Ya no necesitamos actualizar auxiliares porque usan 'codigo', no 'cat_cuentas_id'
                    // Solo verificamos que todos los auxiliares apunten al código correcto
                    $auxActualizados = Auxiliares::where('team_id', $dup->team_id)
                        ->where('codigo', $dup->codigo)
                        ->count();

                    $totalAuxiliaresActualizados += $auxActualizados;

                    // Eliminar relaciones en la tabla pivot cat_cuentas_team
                    foreach ($idsEliminar as $idEliminar) {
                        DB::table('cat_cuentas_team')
                            ->where('cat_cuentas_id', $idEliminar)
                            ->delete();
                    }

                    // Eliminar cuentas duplicadas
                    $eliminados = CatCuentas::whereIn('id', $idsEliminar)->delete();
                    $totalCuentasEliminadas += $eliminados;
                });

                $this->info("  ✓ Consolidado correctamente");
            } else {
                // Verificar si hay registros en cat_cuentas_team para mostrar en dry-run
                $pivotCount = 0;
                foreach ($idsEliminar as $idEliminar) {
                    $pivotCount += DB::table('cat_cuentas_team')
                        ->where('cat_cuentas_id', $idEliminar)
                        ->count();
                }
                if ($pivotCount > 0) {
                    $this->line("  ⊘ [DRY-RUN] Se eliminarían {$pivotCount} registros en cat_cuentas_team");
                }
                $this->line("  ⊘ [DRY-RUN] Se eliminarían " . count($idsEliminar) . " cuentas duplicadas");
            }

            $totalProcesados++;
            $this->newLine();
        }

        // Resumen final
        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line("Grupos de duplicados procesados: {$totalProcesados}");

        if (!$dryRun) {
            $this->line("Auxiliares verificados: {$totalAuxiliaresActualizados}");
            $this->line("Cuentas eliminadas: {$totalCuentasEliminadas}");
            $this->newLine();
            $this->info('✅ Consolidación completada exitosamente');
        } else {
            $this->newLine();
            $this->warn('Ejecute sin --dry-run para aplicar los cambios');
        }

        return 0;
    }
}
