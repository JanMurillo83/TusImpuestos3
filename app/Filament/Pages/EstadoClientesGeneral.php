<?php

namespace App\Filament\Pages;

use App\Models\DatosFiscales;
use App\Models\EstadCXC_F;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class EstadoClientesGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-clientes-general';
    protected static bool $shouldRegisterNavigation = false;
    protected ?string $maxContentWidth = 'full';
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
        $fiscales = DatosFiscales::where('team_id',$team_id)->first();
        $clientes = EstadCXC_F::select(DB::raw("clave,cliente,sum(corriente) as corriente,sum(vencido) as vencido,sum(saldo) as saldo"))
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
}
