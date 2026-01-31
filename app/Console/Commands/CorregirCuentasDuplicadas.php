<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorregirCuentasDuplicadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:corregir-cuentas-duplicadas {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige cuentas contables duplicadas por team_id para clientes (105) y proveedores (201)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se realizarán cambios en la base de datos');
        }

        $duplicados = DB::table('cat_cuentas')
            ->select(
                'team_id',
                'codigo',
                DB::raw('COUNT(*) as total'),
                DB::raw('GROUP_CONCAT(id ORDER BY id) as ids')
            )
            ->where(function ($query) {
                $query->where('codigo', 'like', '10501%')
                    ->orWhere('codigo', 'like', '20101%');
            })
            ->whereNotNull('codigo')
            ->groupBy('team_id', 'codigo')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        if ($duplicados->isEmpty()) {
            $this->info('✓ No se encontraron cuentas duplicadas en clientes/proveedores');
            return 0;
        }

        $this->info('Encontrados ' . $duplicados->count() . ' grupos de cuentas duplicadas');

        $registrosCorregidos = 0;
        $nextCodigoPorTeam = [];

        foreach ($duplicados as $duplicado) {
            $ids = array_filter(explode(',', (string) $duplicado->ids));
            $keepId = array_shift($ids);

            $this->line("\n---");
            $this->info("Team: {$duplicado->team_id}, Codigo: {$duplicado->codigo}");
            $this->line("Manteniendo ID: {$keepId}");
            $this->line('Corrigiendo IDs: ' . implode(', ', $ids));

            $prefix = substr((string) $duplicado->codigo, 0, 5);
            $teamKey = $duplicado->team_id . ':' . $prefix;
            if (!array_key_exists($teamKey, $nextCodigoPorTeam)) {
                $maxCodigo = (int) DB::table('cat_cuentas')
                    ->where('team_id', $duplicado->team_id)
                    ->where('codigo', 'like', $prefix . '%')
                    ->max('codigo');
                $nextCodigoPorTeam[$teamKey] = $maxCodigo + 1;
            }

            foreach ($ids as $idDuplicado) {
                $cuenta = DB::table('cat_cuentas')
                    ->where('id', $idDuplicado)
                    ->first(['id', 'codigo', 'acumula', 'team_id']);

                if (!$cuenta) {
                    continue;
                }

                $nuevoCodigo = $nextCodigoPorTeam[$teamKey];
                while (
                    DB::table('cat_cuentas')
                        ->where('team_id', $cuenta->team_id)
                        ->where('codigo', $nuevoCodigo)
                        ->exists()
                ) {
                    $nuevoCodigo++;
                }

                $this->warn("  - ID {$idDuplicado}: {$cuenta->codigo} -> {$nuevoCodigo}");

                if (!$dryRun) {
                    DB::table('cat_cuentas')
                        ->where('id', $idDuplicado)
                        ->update([
                            'codigo' => (string) $nuevoCodigo,
                            'updated_at' => now(),
                        ]);

                    $this->actualizaRelacionados(
                        $this->tablaPorPrefijo((string) $cuenta->codigo),
                        $cuenta->team_id,
                        (string) $cuenta->codigo,
                        (string) $nuevoCodigo
                    );
                }

                $nextCodigoPorTeam[$teamKey] = $nuevoCodigo + 1;
                $registrosCorregidos++;
            }
        }

        $this->line("\n---");

        if ($dryRun) {
            $this->info("✓ DRY-RUN completado: Se corregirían {$registrosCorregidos} registros");
            $this->line('Ejecuta sin --dry-run para aplicar los cambios');
        } else {
            $this->info("✓ Corrección completada: {$registrosCorregidos} registros corregidos");
        }

        return 0;
    }

    private function tablaPorPrefijo(string $codigo): ?string
    {
        $prefix = substr($codigo, 0, 5);
        if ($prefix === '10501') {
            return 'clientes';
        }
        if ($prefix === '20101') {
            return 'proveedores';
        }
        return null;
    }

    private function actualizaRelacionados(
        ?string $tabla,
        int $teamId,
        string $codigoAnterior,
        string $codigoNuevo
    ): void {
        if ($tabla) {
            DB::table($tabla)
                ->where('team_id', $teamId)
                ->where('cuenta_contable', $codigoAnterior)
                ->update(['cuenta_contable' => $codigoNuevo]);
        }

        DB::table('auxiliares')
            ->where('team_id', $teamId)
            ->where('codigo', $codigoAnterior)
            ->update(['codigo' => $codigoNuevo]);
    }
}
