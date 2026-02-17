<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\CfdiSatScraperService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestCfdiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfdi:test-connection {team_id : ID del team a probar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la conexión y configuración FIEL de un team específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamId = $this->argument('team_id');

        $this->info('==============================================');
        $this->info('Prueba de Conexión CFDI - Team ID: ' . $teamId);
        $this->info('==============================================');
        $this->newLine();

        // Buscar team
        $team = Team::find($teamId);
        if (!$team) {
            $this->error("❌ No se encontró el team con ID: {$teamId}");
            return 1;
        }

        $this->info("✓ Team encontrado: {$team->name}");
        $this->info("  RFC: {$team->taxid}");
        $this->newLine();

        // Inicializar servicio
        $scraperService = new CfdiSatScraperService($team);

        // Paso 1: Validar archivos FIEL
        $this->info('Paso 1: Validando archivos FIEL...');
        $validation = $scraperService->validateFielFiles();

        if ($validation['valid']) {
            $this->info('  ✓ Archivos FIEL encontrados');
            $config = $scraperService->getConfig();
            $this->info("    - CER: {$config['fielcer']}");
            $this->info("    - KEY: {$config['fielkey']}");
            $this->info("    - Pass: " . (empty($config['fielpass']) ? 'NO CONFIGURADA' : '***'));
        } else {
            $this->error("  ❌ {$validation['error']}");
            return 1;
        }
        $this->newLine();

        // Paso 2: Validar credenciales FIEL
        $this->info('Paso 2: Validando credenciales FIEL...');
        $credentialValidation = $scraperService->validateFielCredentials();

        if ($credentialValidation['valid']) {
            $this->info('  ✓ Credenciales FIEL válidas');
            $this->info("    - Vigencia hasta: {$credentialValidation['vigencia']}");
        } else {
            $this->error("  ❌ {$credentialValidation['error']}");
            if (isset($credentialValidation['vigencia'])) {
                $this->error("    - Expiró el: {$credentialValidation['vigencia']}");
            }
            return 1;
        }
        $this->newLine();

        // Paso 3: Inicializar scraper
        $this->info('Paso 3: Inicializando conexión con el SAT...');
        $init = $scraperService->initializeScraper();

        if ($init['valid']) {
            $this->info('  ✓ Conexión con el SAT establecida correctamente');
        } else {
            $this->error("  ❌ {$init['error']}");
            return 1;
        }
        $this->newLine();

        // Paso 4: Prueba de consulta
        $this->info('Paso 4: Realizando consulta de prueba...');
        $this->info('  Consultando CFDIs emitidos del día de ayer...');

        $fechaPrueba = Carbon::yesterday()->format('Y-m-d');

        try {
            $result = $scraperService->listByPeriod($fechaPrueba, $fechaPrueba, 'emitidos', true);

            if ($result['success']) {
                $this->info("  ✓ Consulta exitosa");
                $this->info("    - CFDIs encontrados: {$result['count']}");

                if ($result['count'] > 0) {
                    $this->newLine();
                    $this->info('  Primeros 5 CFDIs:');
                    $count = 0;
                    foreach ($result['list'] as $cfdi) {
                        if ($count >= 5) break;
                        $this->line("    • UUID: {$cfdi->uuid()}");
                        $this->line("      RFC Receptor: {$cfdi->get('rfcReceptor')}");
                        $this->line("      Total: \${$cfdi->get('total')}");
                        $this->line("      Estado: {$cfdi->get('estadoComprobante')}");
                        $count++;
                    }
                }
            } else {
                $this->error("  ❌ Error en la consulta: {$result['error']}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Excepción durante la consulta: {$e->getMessage()}");
            return 1;
        }

        $this->newLine();
        $this->info('==============================================');
        $this->info('✓ Todas las pruebas completadas exitosamente');
        $this->info('==============================================');

        return 0;
    }
}
