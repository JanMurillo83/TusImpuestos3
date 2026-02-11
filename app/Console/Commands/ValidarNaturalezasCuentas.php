<?php

namespace App\Console\Commands;

use App\Models\CatCuentas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidarNaturalezasCuentas extends Command
{
    protected $signature = 'cuentas:validar-naturalezas {--team_id=} {--corregir}';
    protected $description = 'Valida y corrige las naturalezas de las cuentas contables según su código';

    private $reglasNaturaleza = [
        // Activo (1) - Deudora
        '1' => 'D',
        // Pasivo (2) - Acreedora
        '2' => 'A',
        // Capital (3) - Acreedora
        '3' => 'A',
        // Ingresos (4) - Acreedora
        '4' => 'A',
        // Costos (5) - Deudora
        '5' => 'D',
        // Gastos (6) - Deudora
        '6' => 'D',
        // Cuentas de orden (7) - Depende del subcódigo
        '7' => null,
        // Resultados (8) - Depende
        '8' => null,
    ];

    public function handle()
    {
        $teamId = $this->option('team_id');
        $corregir = $this->option('corregir');

        $this->info('=== Validación de Naturalezas de Cuentas ===');
        $this->info('');

        $query = CatCuentas::query();

        if ($teamId) {
            $query->where('team_id', $teamId);
            $this->info("Filtrando por team_id: $teamId");
        }

        $cuentas = $query->orderBy('codigo')->get();

        $errores = [];
        $correctas = 0;

        foreach ($cuentas as $cuenta) {
            $naturalezaEsperada = $this->determinarNaturalezaEsperada($cuenta->codigo);

            if ($naturalezaEsperada === null) {
                // No se puede determinar automáticamente
                continue;
            }

            if ($cuenta->naturaleza !== $naturalezaEsperada) {
                $errores[] = [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'actual' => $cuenta->naturaleza ?? 'NULL',
                    'esperada' => $naturalezaEsperada,
                ];
            } else {
                $correctas++;
            }
        }

        $this->info("Cuentas analizadas: {$cuentas->count()}");
        $this->info("Cuentas correctas: $correctas");
        $this->info("Cuentas con naturaleza incorrecta: " . count($errores));
        $this->info('');

        if (count($errores) > 0) {
            $this->warn('=== Cuentas con Naturaleza Incorrecta ===');
            $this->table(
                ['ID', 'Código', 'Nombre', 'Actual', 'Esperada'],
                array_map(fn($e) => [
                    $e['id'],
                    $e['codigo'],
                    substr($e['nombre'], 0, 40),
                    $e['actual'],
                    $e['esperada']
                ], $errores)
            );

            if ($corregir) {
                if ($this->confirm('¿Desea corregir estas ' . count($errores) . ' cuentas?')) {
                    $this->corregirNaturalezas($errores);
                } else {
                    $this->info('Corrección cancelada.');
                }
            } else {
                $this->info('');
                $this->comment('Para corregir automáticamente, ejecute el comando con la opción --corregir');
            }
        } else {
            $this->info('✓ Todas las cuentas tienen la naturaleza correcta');
        }

        return 0;
    }

    private function determinarNaturalezaEsperada(string $codigo): ?string
    {
        if (empty($codigo)) {
            return null;
        }

        $primerDigito = substr($codigo, 0, 1);

        // Para cuentas de orden (7), analizar subcuenta
        if ($primerDigito === '7') {
            // 7.01 a 7.04 típicamente deudoras (activos contingentes)
            // 7.05 a 7.08 típicamente acreedoras (pasivos contingentes)
            if (preg_match('/^7\.0[1-4]/', $codigo)) {
                return 'D';
            } elseif (preg_match('/^7\.0[5-8]/', $codigo)) {
                return 'A';
            }
            // Si no se puede determinar, retornar null
            return null;
        }

        // Para cuentas de resultado integral (8), depende del tipo
        if ($primerDigito === '8') {
            // 8.01 Resultado del ejercicio - puede ser cualquiera
            // 8.02 Otros resultados integrales - puede ser cualquiera
            return null;
        }

        return $this->reglasNaturaleza[$primerDigito] ?? null;
    }

    private function corregirNaturalezas(array $errores): void
    {
        $this->info('');
        $this->info('Corrigiendo naturalezas...');

        DB::beginTransaction();

        try {
            $corregidas = 0;

            foreach ($errores as $error) {
                CatCuentas::where('id', $error['id'])
                    ->update(['naturaleza' => $error['esperada']]);

                $corregidas++;

                $this->line("✓ Cuenta {$error['codigo']} - {$error['nombre']}: {$error['actual']} → {$error['esperada']}");
            }

            DB::commit();

            $this->info('');
            $this->info("✓ Se corrigieron $corregidas cuentas exitosamente");
            $this->comment('Las pólizas existentes NO fueron modificadas');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error al corregir naturalezas: ' . $e->getMessage());
            return;
        }
    }
}
