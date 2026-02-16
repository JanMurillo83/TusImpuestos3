<?php

namespace App\Console\Commands;

use App\Services\SaldosHealthCheck;
use App\Services\SaldosMetrics;
use Illuminate\Console\Command;

/**
 * FASE 3: Health check automático del sistema de saldos
 */
class SaldosHealthCheckCommand extends Command
{
    protected $signature = 'saldos:health-check
                            {--team= : ID del team específico}
                            {--fix : Intentar auto-corregir problemas}';

    protected $description = 'FASE 3: Ejecutar health checks del sistema de saldos contables';

    public function handle()
    {
        $this->info("🏥 Ejecutando Health Checks del Sistema de Saldos");
        $this->newLine();

        $team_id = $this->option('team');
        $autoFix = $this->option('fix');

        // Ejecutar todos los checks
        $results = SaldosHealthCheck::runAllChecks($team_id);

        // Mostrar resultados
        foreach ($results as $result) {
            $icon = $result['status'] === 'pass' ? '✅' : ($result['status'] === 'warning' ? '⚠️' : '❌');
            $color = $result['status'] === 'pass' ? 'green' : ($result['status'] === 'warning' ? 'yellow' : 'red');

            $this->line("{$icon} <fg={$color}>{$result['check']}</>: {$result['message']}");

            if (isset($result['details']) && is_array($result['details'])) {
                foreach ($result['details'] as $key => $value) {
                    if (!is_array($value) && !is_object($value)) {
                        $this->line("   • {$key}: {$value}");
                    }
                }
            }

            $this->newLine();
        }

        // Mostrar resumen
        $passed = collect($results)->where('status', 'pass')->count();
        $warnings = collect($results)->where('status', 'warning')->count();
        $failed = collect($results)->where('status', 'fail')->count();

        $this->info("═══════════════════════════════════════");
        $this->info("Resumen:");
        $this->line("  ✅ Passed: {$passed}");
        $this->line("  ⚠️  Warnings: {$warnings}");
        $this->line("  ❌ Failed: {$failed}");
        $this->info("═══════════════════════════════════════");

        // Auto-fix si se solicitó
        if ($autoFix && $failed > 0 && $team_id) {
            $this->warn("Intentando auto-corrección...");
            // Implementar lógica de auto-fix
        }

        return $failed > 0 ? 1 : 0;
    }
}
