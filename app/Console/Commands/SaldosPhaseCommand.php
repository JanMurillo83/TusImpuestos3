<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Comando para gestionar Fase 1 y Fase 2 del sistema de saldos contables
 *
 * Permite habilitar/deshabilitar:
 * - Fase 1: Caché estratégico
 * - Fase 2: Event-driven updates automáticos
 */
class SaldosPhaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saldos:phase
                            {action : enable, disable, status, restart-worker}
                            {--phase=all : Especificar fase (1, 2, all)}
                            {--supervisor : Generar configuración de supervisor}
                            {--force : Forzar acción sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar Fase 1 (caché) y Fase 2 (event-driven) del sistema de saldos contables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $phase = $this->option('phase');

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("   Sistema de Saldos Contables - Gestión de Fases");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        switch ($action) {
            case 'enable':
                $this->enablePhase($phase);
                break;
            case 'disable':
                $this->disablePhase($phase);
                break;
            case 'status':
                $this->showStatus();
                break;
            case 'restart-worker':
                $this->restartWorker();
                break;
            default:
                $this->error("Acción no válida. Use: enable, disable, status, restart-worker");
                return 1;
        }

        if ($this->option('supervisor')) {
            $this->generateSupervisorConfig();
        }

        return 0;
    }

    /**
     * Habilitar fase(s)
     */
    protected function enablePhase($phase)
    {
        $this->info("🚀 Habilitando sistema de saldos optimizado...");
        $this->newLine();

        if ($phase === 'all' || $phase === '1') {
            $this->enablePhase1();
        }

        if ($phase === 'all' || $phase === '2') {
            $this->enablePhase2();
        }

        $this->newLine();
        $this->info("✅ Configuración completada");
        $this->showStatus();
    }

    /**
     * Habilitar Fase 1 (Caché)
     */
    protected function enablePhase1()
    {
        $this->line("📦 <fg=cyan>FASE 1: Caché Estratégico</>");
        $this->line("   • TTL: 5 minutos (300 segundos)");
        $this->line("   • Driver: " . config('cache.default'));
        $this->line("   • Estado: <fg=green>SIEMPRE ACTIVA</> (implementada en código)");
        $this->newLine();
    }

    /**
     * Habilitar Fase 2 (Event-Driven)
     */
    protected function enablePhase2()
    {
        $this->line("⚡ <fg=cyan>FASE 2: Event-Driven Updates</>");

        if (!$this->option('force')) {
            if (!$this->confirm('¿Desea habilitar actualización automática de saldos?', true)) {
                $this->warn('   Fase 2 no habilitada');
                return;
            }
        }

        // Actualizar .env
        $this->updateEnvVariable('SALDOS_AUTO_UPDATE', 'true');
        $this->updateEnvVariable('SALDOS_CACHE_TTL', '300');
        $this->updateEnvVariable('SALDOS_QUEUE', 'saldos');
        $this->updateEnvVariable('SALDOS_JOB_TIMEOUT', '120');
        $this->updateEnvVariable('SALDOS_JOB_TRIES', '3');
        $this->updateEnvVariable('SALDOS_DETAILED_LOGGING', 'false');

        // Limpiar config
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        $this->line("   • Auto-update: <fg=green>HABILITADO</>");
        $this->line("   • Queue: saldos");
        $this->line("   • Timeout: 120s");
        $this->newLine();

        // Verificar si queue worker está corriendo
        $workerRunning = $this->checkQueueWorker();

        if (!$workerRunning) {
            $this->warn("⚠️  Queue worker NO está corriendo");
            $this->newLine();
            $this->line("Para iniciar el worker, ejecute:");
            $this->line("  <fg=yellow>php artisan queue:work --queue=saldos --tries=3 --timeout=120</>");
            $this->newLine();
            $this->line("O para producción con Supervisor:");
            $this->line("  <fg=yellow>php artisan saldos:phase enable --supervisor</>");
            $this->newLine();

            if ($this->confirm('¿Desea iniciar el queue worker ahora?', false)) {
                $this->startQueueWorker();
            }
        } else {
            $this->info("✅ Queue worker está corriendo");
        }
    }

    /**
     * Deshabilitar fase(s)
     */
    protected function disablePhase($phase)
    {
        $this->warn("🛑 Deshabilitando sistema de saldos optimizado...");
        $this->newLine();

        if ($phase === '1') {
            $this->line("📦 <fg=yellow>FASE 1:</> No se puede deshabilitar (implementada en código)");
            $this->line("   Para desactivar caché manualmente, modificar código fuente");
            $this->newLine();
        }

        if ($phase === 'all' || $phase === '2') {
            $this->disablePhase2();
        }

        $this->newLine();
        $this->info("✅ Configuración actualizada");
        $this->showStatus();
    }

    /**
     * Deshabilitar Fase 2
     */
    protected function disablePhase2()
    {
        $this->line("⚡ <fg=cyan>FASE 2: Event-Driven Updates</>");

        if (!$this->option('force')) {
            if (!$this->confirm('¿Desea DESHABILITAR actualización automática de saldos?', false)) {
                $this->info('   Fase 2 permanece habilitada');
                return;
            }
        }

        // Actualizar .env
        $this->updateEnvVariable('SALDOS_AUTO_UPDATE', 'false');

        // Limpiar config
        Artisan::call('config:clear');

        $this->line("   • Auto-update: <fg=red>DESHABILITADO</>");
        $this->line("   • Sistema volverá a método manual (ContabilizaReporte)");
        $this->newLine();

        $this->warn("⚠️  Recuerde detener el queue worker si ya no es necesario:");
        $this->line("  <fg=yellow>php artisan queue:restart</>");
    }

    /**
     * Mostrar estado actual
     */
    protected function showStatus()
    {
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("   ESTADO ACTUAL DEL SISTEMA");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        // Fase 1
        $this->line("📦 <fg=cyan;options=bold>FASE 1: Caché Estratégico</>");
        $this->line("   Estado: <fg=green>ACTIVA</> (siempre habilitada)");
        $this->line("   TTL: " . config('saldos.cache_ttl', 300) . " segundos");
        $this->line("   Driver: " . config('cache.default'));
        $this->newLine();

        // Fase 2
        $autoUpdate = config('saldos.auto_update_enabled', false);
        $statusColor = $autoUpdate ? 'green' : 'red';
        $statusText = $autoUpdate ? 'HABILITADA' : 'DESHABILITADA';

        $this->line("⚡ <fg=cyan;options=bold>FASE 2: Event-Driven Updates</>");
        $this->line("   Estado: <fg={$statusColor}>{$statusText}</>");
        $this->line("   Queue: " . config('saldos.queue_name', 'saldos'));
        $this->line("   Timeout: " . config('saldos.job_timeout', 120) . "s");
        $this->line("   Reintentos: " . config('saldos.job_tries', 3));
        $this->newLine();

        // Queue Worker
        $workerRunning = $this->checkQueueWorker();
        $workerColor = $workerRunning ? 'green' : 'red';
        $workerStatus = $workerRunning ? 'CORRIENDO' : 'DETENIDO';

        $this->line("🔧 <fg=cyan;options=bold>Queue Worker</>");
        $this->line("   Estado: <fg={$workerColor}>{$workerStatus}</>");

        if ($workerRunning) {
            $processes = shell_exec("ps aux | grep 'queue:work' | grep -v grep");
            if ($processes) {
                $this->line("   Procesos activos:");
                foreach (explode("\n", trim($processes)) as $process) {
                    if (!empty($process)) {
                        preg_match('/\s+(\d+)\s+/', $process, $matches);
                        $pid = $matches[1] ?? 'N/A';
                        $this->line("     • PID: {$pid}");
                    }
                }
            }
        }
        $this->newLine();

        // Database Queue
        $this->line("💾 <fg=cyan;options=bold>Database Queue</>");
        $this->line("   Conexión: " . config('queue.default'));

        try {
            $pendingJobs = \DB::table('jobs')->where('queue', 'saldos')->count();
            $failedJobs = \DB::table('failed_jobs')->count();

            $this->line("   Jobs pendientes: {$pendingJobs}");
            $this->line("   Jobs fallidos: {$failedJobs}");
        } catch (\Exception $e) {
            $this->line("   <fg=yellow>No se pudo consultar tabla jobs</>");
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
    }

    /**
     * Reiniciar queue worker
     */
    protected function restartWorker()
    {
        $this->info("🔄 Reiniciando queue worker...");
        $this->newLine();

        Artisan::call('queue:restart');

        $this->info("✅ Señal de reinicio enviada");
        $this->line("   Los workers actuales terminarán su job actual y se reiniciarán");
        $this->newLine();

        sleep(2);

        $workerRunning = $this->checkQueueWorker();

        if ($workerRunning) {
            $this->info("✅ Queue worker reiniciado correctamente");
        } else {
            $this->warn("⚠️  No se detectan workers corriendo");
            $this->line("Inicie un nuevo worker con:");
            $this->line("  <fg=yellow>php artisan queue:work --queue=saldos --tries=3 --timeout=120 &</>");
        }
    }

    /**
     * Generar configuración de Supervisor
     */
    protected function generateSupervisorConfig()
    {
        $this->newLine();
        $this->info("📝 Generando configuración de Supervisor...");
        $this->newLine();

        $projectPath = base_path();
        $user = get_current_user();
        $storagePath = storage_path('logs');

        $config = <<<EOF
[program:tusimpuestos-saldos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php {$projectPath}/artisan queue:work --queue=saldos --tries=3 --timeout=120 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user={$user}
numprocs=2
redirect_stderr=true
stdout_logfile={$storagePath}/saldos-worker.log
stopwaitsecs=3600

EOF;

        $configPath = storage_path('supervisor-saldos.conf');
        File::put($configPath, $config);

        $this->info("✅ Configuración generada en:");
        $this->line("   {$configPath}");
        $this->newLine();

        $this->line("Para instalar en Supervisor:");
        $this->line("  1. <fg=yellow>sudo cp {$configPath} /etc/supervisor/conf.d/</>");
        $this->line("  2. <fg=yellow>sudo supervisorctl reread</>");
        $this->line("  3. <fg=yellow>sudo supervisorctl update</>");
        $this->line("  4. <fg=yellow>sudo supervisorctl start tusimpuestos-saldos-worker:*</>");
        $this->newLine();

        $this->line("Verificar estado:");
        $this->line("  <fg=yellow>sudo supervisorctl status tusimpuestos-saldos-worker:*</>");
        $this->newLine();
    }

    /**
     * Iniciar queue worker en background
     */
    protected function startQueueWorker()
    {
        $this->info("🚀 Iniciando queue worker...");

        $command = "php " . base_path('artisan') . " queue:work --queue=saldos --tries=3 --timeout=120 > /dev/null 2>&1 &";
        shell_exec($command);

        sleep(2);

        if ($this->checkQueueWorker()) {
            $this->info("✅ Queue worker iniciado correctamente");
        } else {
            $this->error("❌ No se pudo iniciar el queue worker");
            $this->line("Intente manualmente con:");
            $this->line("  <fg=yellow>php artisan queue:work --queue=saldos --tries=3 --timeout=120 &</>");
        }
    }

    /**
     * Verificar si queue worker está corriendo
     */
    protected function checkQueueWorker(): bool
    {
        $processes = shell_exec("ps aux | grep 'queue:work' | grep 'saldos' | grep -v grep");
        return !empty(trim($processes ?? ''));
    }

    /**
     * Actualizar variable en .env
     */
    protected function updateEnvVariable($key, $value)
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            $this->error("Archivo .env no encontrado");
            return;
        }

        $content = File::get($path);

        // Verificar si la variable ya existe
        if (preg_match("/^{$key}=/m", $content)) {
            // Actualizar valor existente
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            // Agregar nueva variable al final
            $content .= "\n{$key}={$value}\n";
        }

        File::put($path, $content);
    }
}
