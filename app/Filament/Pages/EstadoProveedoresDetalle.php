<?php

namespace App\Filament\Pages;

use App\Http\Controllers\MainChartsController;
use App\Models\Clientes;
use App\Models\DatosFiscales;
use App\Models\EstadCXC_F;
use App\Models\EstadCXP_F;
use App\Models\Proveedores;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class EstadoProveedoresDetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estado-proveedores-detalle';
    protected static bool $shouldRegisterNavigation = false;
    #[Url]
    public ?string $cliente = null;
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
        $maindata = EstadCXP_F::where('clave',$this->cliente)->first();
        $facturas = $maindata->facturas;
        $datacliente = Proveedores::where('cuenta_contable',$this->cliente)->first();
        //dd($this->cliente,$maindata,$facturas);
        $mes_actual = app(MainChartsController::class)->mes_letras(Filament::getTenant()->periodo);
        return [
            'empresa'=>$empresa,'team_id'=>$team_id,'ejercicio' => $ejercicio,
            'periodo' => $periodo,'clave'=>$this->cliente,'maindata'=>$maindata,
            'facturas'=>$facturas,'datacliente'=>$datacliente,'mes_actual'=>$mes_actual,
            'emp_correo'=>$fiscales?->correo ?? 'xxxxx@xxxxxx.com','emp_telefono'=>$fiscales?->telefono ?? '0000000000'
        ];
    }
}
