<?php

namespace App\Filament\Pages;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Livewire\AdvVentasPeriodoWidget;
use App\Models\AuxVentas;
use App\Models\DatosFiscales;
use Fibtegis\FilamentInfiniteScroll\Concerns\InteractsWithInfiniteScroll;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use App\Http\Controllers\MainChartsController;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class Ventasperiododetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.ventasperiododetalle';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Ventas por Periodo';
    public ?string $file_title = '';
    public function getTitle(): string
    {
        return '';

    }
    protected function getViewData(): array
    {
        $team_id = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $periodo_ant = Filament::getTenant()->periodo - 1;
        $ejercicio = Filament::getTenant()->ejercicio;
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        $mes_letras_ant = app(MainChartsController::class)->mes_letras($periodo_ant);
        $empresa = Filament::getTenant()->name;
        $maindata = AuxVentas::select(DB::raw("concepto as cliente, sum(abono) as importe"))
        ->groupBy('cliente')->orderBy('importe','desc')->get();
        $importe_mes = floatval(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo,$ejercicio));
        $importe_ant = floatval(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo_ant,$ejercicio));
        $fiscales = DatosFiscales::where('team_id',$team_id)->first();
        return [
            'empresa'=>$empresa,'team_id'=>$team_id,'ejercicio' => $ejercicio,'maindata'=>$maindata,
            'mes_letras'=>$mes_letras,'mes_letras_ant'=>$mes_letras_ant,'importe_mes'=>$importe_mes,'importe_ant'=>$importe_ant,
            'emp_correo'=>$fiscales?->correo ?? 'xxxxx@xxxxxx.com','emp_telefono'=>$fiscales?->telefono ?? '0000000000'
        ];
    }


}
