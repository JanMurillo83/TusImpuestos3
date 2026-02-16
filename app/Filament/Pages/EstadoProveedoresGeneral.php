<?php

namespace App\Filament\Pages;

use App\Exports\EstadoProveedoresExport;
use App\Models\DatosFiscales;
use App\Models\EstadCXC_F;
use App\Models\EstadCXP_F;
use App\Models\Proveedores;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Browsershot\Browsershot;

class EstadoProveedoresGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-proveedores-general';
    protected static bool $shouldRegisterNavigation = false;
    public function getTitle(): string
    {
        return '';
    }
    public function getViewData(): array
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $empresa = Filament::getTenant()->name;

        // OPCIÓN 3: Regenerar saldos_reportes para asegurar consistencia con balanza
        app(\App\Http\Controllers\ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $fiscales = DatosFiscales::where('team_id',$team_id)->first();
        $clientes = EstadCXP_F::select(DB::raw("clave,cliente,sum(corriente) as corriente,sum(vencido) as vencido,sum(saldo) as saldo"))
            ->groupBy('clave')->groupBy('cliente')->where('saldo','!=',0)->get();
        return [
            'empresa'=>$empresa,'team_id'=>$team_id,'ejercicio' => $ejercicio,
            'periodo' => $periodo,'maindata'=>$clientes,
            'saldo_corriente'=>$clientes->sum('corriente'),
            'saldo_vencido'=>$clientes->sum('vencido'),
            'saldo_total'=>$clientes->sum('saldo'),
            'emp_correo'=>$fiscales?->correo ?? 'xxxxx@xxxxxx.com','emp_telefono'=>$fiscales?->telefono ?? '0000000000'
        ];
    }

    public function exportarPDF()
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $empresa = Filament::getTenant()->name;
        $fiscales = DatosFiscales::where('team_id',$team_id)->first();
        $clientes = EstadCXP_F::select(DB::raw("clave,cliente,sum(corriente) as corriente,sum(vencido) as vencido,sum(saldo) as saldo"))
            ->groupBy('clave')->groupBy('cliente')->where('saldo','!=',0)->get();

        $data = [
            'empresa'=>$empresa,
            'team_id'=>$team_id,
            'ejercicio' => $ejercicio,
            'periodo' => $periodo,
            'maindata'=>$clientes,
            'saldo_corriente'=>$clientes->sum('corriente'),
            'saldo_vencido'=>$clientes->sum('vencido'),
            'saldo_total'=>$clientes->sum('saldo'),
            'emp_correo'=>$fiscales?->correo ?? 'xxxxx@xxxxxx.com',
            'emp_telefono'=>$fiscales?->telefono ?? '0000000000'
        ];

        $ruta = public_path('TMPCFDI/EstadoProveedoresGeneral_'.$team_id.'.pdf');
        $html = View::make('EstadoCXPGeneral', $data)->render();

        Browsershot::html($html)
            ->format('Letter')
            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
            ->setEnvironmentOptions([
                "XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing",
                "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"
            ])
            ->noSandbox()
            ->scale(0.8)
            ->savePdf($ruta);

        return response()->download($ruta);
    }

    public function exportarExcel()
    {
        $team_id = Filament::getTenant()->id;
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;

        return Excel::download(
            new EstadoProveedoresExport($team_id, $ejercicio, $periodo),
            'EstadoProveedores_'.$team_id.'_'.$periodo.'_'.$ejercicio.'.xlsx'
        );
    }
}
