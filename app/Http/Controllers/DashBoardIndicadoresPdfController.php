<?php

namespace App\Http\Controllers;

use App\Filament\Pages\DashBoardIndicadores;
use App\Http\Controllers\MainChartsController;
use App\Http\Controllers\ReportesController;
use App\Models\DatosFiscales;
use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Facades\Filament;

class DashBoardIndicadoresPdfController extends Controller
{
    public function __invoke(string $tenantSlug)
    {
        $team = Team::findOrFail($tenantSlug);
        $user = auth()->user();
        if (! $user || ! $user->canAccessTenant($team)) {
            abort(403);
        }
        Filament::setTenant($team);

        if (! DashBoardIndicadores::canAccess()) {
            abort(403);
        }

        app(ReportesController::class)->ContabilizaReporte($team->ejercicio, $team->periodo, $team->id);

        $importes = DashBoardIndicadores::getCalcs();
        $fiscales = DatosFiscales::where('team_id', $team->id)->first();
        $user = $user ?? auth()->user();

        $logoPath = public_path('images/MainLogoTR.png');
        $logoData = null;
        if (is_file($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $data = [
            'team_id' => $team->id,
            'periodo' => $team->periodo,
            'ejercicio' => $team->ejercicio,
            'usuario' => $user?->name ?? 'Usuario',
            'fecha' => Carbon::now()->format('d/m/Y'),
            'mes_actual' => app(MainChartsController::class)->mes_letras($team->periodo),
            'ventas' => $importes['ventas'],
            'ventas_pa' => $importes['ventas_pa'],
            'ventas_dif' => $importes['ventas_dif'],
            'ventas_anuales' => $importes['ventas_anuales'],
            'cobrar_importe' => $importes['cobrar_importe'],
            'cuentas_x_cobrar_top3' => $importes['cuentas_x_cobrar_top3'],
            'importe_vencido' => $importes['importe_vencido'],
            'pagar_importe' => $importes['pagar_importe'],
            'cuentas_x_pagar_top3' => $importes['cuentas_x_pagar_top3'],
            'utilidad_importe' => $importes['utilidad_importe'],
            'utilidad_ejercicio' => $importes['utilidad_ejercicio'],
            'impuesto_estimado' => $importes['impuesto_estimado'],
            'importe_inventario' => $importes['importe_inventario'],
            'impuesto_mensual' => $importes['impuesto_mensual'],
            'importe_iva' => $importes['importe_iva'],
            'importe_ret' => $importes['importe_ret'],
            'mes_ventas_data' => $importes['mes_ventas_data'],
            'anio_ventas_data' => $importes['anio_ventas_data'],
            'saldo_bancos' => $importes['saldo_bancos'],
            'emp_correo' => $fiscales?->correo ?? 'xxxxx@xxxxxx.com',
            'emp_telefono' => $fiscales?->telefono ?? '0000000000',
            'logo_data' => $logoData,
        ];

        $pdf = Pdf::loadView('filament.pages.dash-board-indicadores-pdf', $data)
            ->setPaper('letter', 'portrait');

        $filename = 'dashboard-indicadores-' . $team->id . '-' . $team->periodo . '-' . $team->ejercicio . '.pdf';

        return $pdf->download($filename);
    }
}
