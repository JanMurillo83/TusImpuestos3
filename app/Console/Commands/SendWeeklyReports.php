<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\WeeklyReportsMail;

class SendWeeklyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-weekly {--team_id=} {--to=} {--at=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera y envía por correo los reportes semanales cada lunes';

    public function handle(): int
    {
        $recipient = $this->option('to') ?: env('WEEKLY_REPORTS_EMAIL');
        if (! $recipient) {
            $this->error('No se ha configurado el correo destinatario. Defina WEEKLY_REPORTS_EMAIL en .env o use --to=.');
            return self::FAILURE;
        }

        $teamId = (int) ($this->option('team_id') ?: env('REPORTS_TEAM_ID', 1));

        // Determina el rango de la semana anterior (lunes a domingo)
        $now = Carbon::now();
        $lastMonday = (clone $now)->startOfWeek(Carbon::MONDAY)->subWeek();
        $lastSunday = (clone $lastMonday)->endOfWeek(Carbon::SUNDAY);

        // Permitir sobreescritura de hora mediante opción/env (no afecta contenido, solo título)
        $sendAt = $this->option('at') ?: env('WEEKLY_REPORTS_TIME', '08:00');

        $fechaInicio = $lastMonday->toDateString();
        $fechaFin = $lastSunday->toDateString();

        $this->info("Generando reportes para el equipo {$teamId} del {$fechaInicio} al {$fechaFin}...");

        $files = [];
        try {
            // Asegura directorio temporal
            $disk = Storage::disk('local');
            $dir = 'reports/weekly/'.Carbon::now()->format('Ymd_His');

            // Define los reportes a generar: vista => [datos]
            $reports = [
                'Estado de Cuenta de Clientes' => ['view' => 'EstadoCuentaClientes', 'data' => [
                    'team' => $teamId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]],
                'Estado de Cuenta de Proveedores' => ['view' => 'EstadoCuentaProveedores', 'data' => [
                    'team' => $teamId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]],
                'Costo del Inventario' => ['view' => 'CostoInventario', 'data' => [
                    'team' => $teamId,
                ]],
                'Reporte de Facturación' => ['view' => 'RepFacturacion', 'data' => [
                    'team' => $teamId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]],
                'Reporte de Compras' => ['view' => 'RepCompras', 'data' => [
                    'team' => $teamId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]],
            ];

            foreach ($reports as $title => $cfg) {
                $pdf = PDF::loadView($cfg['view'], $cfg['data'])->setPaper('letter');
                $filename = sprintf('%s/%s_%s_a_%s.pdf', $dir, str_replace(' ', '_', $title), $fechaInicio, $fechaFin);
                $disk->put($filename, $pdf->output());
                $path = storage_path('app/'.$filename);
                $files[] = ['path' => $path, 'name' => basename($path)];
                $this->line("✓ {$title} generado: ".basename($path));
            }
        } catch (\Throwable $e) {
            $this->error('Error generando los reportes: '.$e->getMessage());
            report($e);
            return self::FAILURE;
        }

        try {
            Mail::to($recipient)->send(new WeeklyReportsMail(
                fechaInicio: $fechaInicio,
                fechaFin: $fechaFin,
                teamId: $teamId,
                sendAt: $sendAt,
                attachments: $files,
            ));
        } catch (\Throwable $e) {
            $this->error('Error enviando el correo: '.$e->getMessage());
            report($e);
            return self::FAILURE;
        }

        $this->info('Reportes enviados correctamente a '.$recipient.'.');
        return self::SUCCESS;
    }
}
