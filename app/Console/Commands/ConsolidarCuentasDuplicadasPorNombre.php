<?php

namespace App\Console\Commands;

use App\Models\Auxiliares;
use App\Models\CatCuentas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidarCuentasDuplicadasPorNombre extends Command
{
    protected $signature = 'cuentas:consolidar-duplicadas-nombre {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios} {--team-id= : Procesar solo un team_id específico}';

    protected $description = 'Consolida cuentas duplicadas de deudores/acreedores (códigos 10501*/20101*) con mismo nombre y team_id';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificTeamId = $this->option('team-id');

        $this->info('=== CONSOLIDACIÓN DE DEUDORES/ACREEDORES DUPLICADOS ===');
        $this->info('    (Códigos 10501* = Deudores, 20101* = Acreedores)');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos');
            $this->newLine();
        }

        // Paso 1: Identificar duplicados por nombre
        $this->info('Paso 1: Identificando cuentas duplicadas por nombre...');
        $this->info('   (Solo cuentas con código 10501* o 20101* - Deudores/Acreedores)');
        $this->newLine();

        $query = DB::table('cat_cuentas')
            ->select('team_id', 'nombre', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'), DB::raw('GROUP_CONCAT(codigo) as codigos'))
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->where(function($q) {
                $q->where('codigo', 'LIKE', '10501%')
                  ->orWhere('codigo', 'LIKE', '20101%');
            })
            ->groupBy('team_id', 'nombre')
            ->having('count', '>', 1);

        if ($specificTeamId) {
            $query->where('team_id', $specificTeamId);
        }

        $duplicados = $query->get();

        if ($duplicados->isEmpty()) {
            $this->info('✅ No se encontraron cuentas duplicadas por nombre.');
            return 0;
        }

        $this->warn("❌ Se encontraron {$duplicados->count()} grupos de cuentas duplicadas por nombre");
        $this->newLine();

        // Mostrar resumen de duplicados
        $table = [];
        foreach ($duplicados as $dup) {
            $ids = explode(',', $dup->ids);
            $codigos = explode(',', $dup->codigos);

            $table[] = [
                'Team ID' => $dup->team_id,
                'Nombre' => strlen($dup->nombre) > 40 ? substr($dup->nombre, 0, 37) . '...' : $dup->nombre,
                'Códigos' => implode(', ', $codigos),
                'IDs' => $dup->ids,
                'Total' => $dup->count
            ];
        }

        $this->table(['Team ID', 'Nombre', 'Códigos', 'IDs', 'Total'], $table);
        $this->newLine();

        // Advertencia especial
        $this->warn('⚠️  ADVERTENCIA: Estas cuentas de DEUDORES/ACREEDORES tienen el MISMO NOMBRE');
        $this->warn('⚠️  Probablemente sea el mismo cliente/proveedor registrado múltiples veces');
        $this->newLine();

        if (!$dryRun && !$this->confirm('¿Está SEGURO de que desea consolidar estas cuentas?', false)) {
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
            $codigos = explode(',', $dup->codigos);

            // La cuenta a mantener será la primera (ID más antiguo)
            $idMantener = $ids[0];
            $codigoMantener = $codigos[0];
            $idsEliminar = array_slice($ids, 1);
            $codigosEliminar = array_slice($codigos, 1);

            $cuentaMantener = CatCuentas::find($idMantener);

            $this->line("Procesando: Team {$dup->team_id} - Nombre '{$dup->nombre}'");
            $this->line("  → Manteniendo: ID {$idMantener}, Código {$codigoMantener}");
            $this->line("  → Eliminando: " . implode(', ', array_map(function($id, $cod) {
                return "ID {$id} (Código {$cod})";
            }, $idsEliminar, $codigosEliminar)));

            // Contar auxiliares que apuntan a los códigos a eliminar
            $auxiliaresAfectados = [];
            foreach ($codigosEliminar as $codigo) {
                $count = Auxiliares::where('team_id', $dup->team_id)
                    ->where('codigo', $codigo)
                    ->count();
                if ($count > 0) {
                    $auxiliaresAfectados[$codigo] = $count;
                }
            }

            if (!empty($auxiliaresAfectados)) {
                $this->warn("  ⚠️  Auxiliares que serán actualizados:");
                foreach ($auxiliaresAfectados as $codigo => $count) {
                    $this->line("     - Código {$codigo}: {$count} auxiliares → se cambiarán a código {$codigoMantener}");
                }
            } else {
                $this->line("  → No hay auxiliares afectados");
            }

            if (!$dryRun) {
                DB::transaction(function() use ($dup, $idMantener, $codigoMantener, $idsEliminar, $codigosEliminar, &$totalAuxiliaresActualizados, &$totalCuentasEliminadas) {
                    // Actualizar auxiliares que apuntan a los códigos que se van a eliminar
                    foreach ($codigosEliminar as $codigoEliminar) {
                        $updated = Auxiliares::where('team_id', $dup->team_id)
                            ->where('codigo', $codigoEliminar)
                            ->update(['codigo' => $codigoMantener]);

                        $totalAuxiliaresActualizados += $updated;
                    }

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
            $this->line("Auxiliares actualizados: {$totalAuxiliaresActualizados}");
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
