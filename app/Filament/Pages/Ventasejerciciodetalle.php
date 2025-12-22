<?php

namespace App\Filament\Pages;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Http\Controllers\MainChartsController;
use App\Models\AuxVentas;
use App\Models\AuxVentasEjercicio;
use App\Models\DatosFiscales;
use Fibtegis\FilamentInfiniteScroll\Concerns\InteractsWithInfiniteScroll;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class Ventasejerciciodetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.ventasejerciciodetalle';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Ventas  del Ejercicio';
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
        $ejercicio_ant = Filament::getTenant()->ejercicio - 1;
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        $mes_letras_ant = app(MainChartsController::class)->mes_letras($periodo_ant);
        $empresa = Filament::getTenant()->name;
        $maindata = AuxVentasEjercicio::select(DB::raw("concepto as cliente, sum(abono) as importe"))
            ->groupBy('cliente')->orderBy('importe','desc')->get();
        $importe_mes = floatval(app(MainChartsController::class)->GetAuxiliaresEjercicio($team_id,'40100000',$periodo,$ejercicio)->sum('abono'));
        $importe_ant = floatval(app(MainChartsController::class)->GetAuxiliaresEjercicio($team_id,'40100000',12,$ejercicio_ant)->sum('abono'));
        $fiscales = DatosFiscales::where('team_id',$team_id)->first();
        return [
            'empresa'=>$empresa,'team_id'=>$team_id,'ejercicio' => $ejercicio,'maindata'=>$maindata,
            'mes_letras'=>$mes_letras,'mes_letras_ant'=>$mes_letras_ant,'importe_mes'=>$importe_mes,'importe_ant'=>$importe_ant,
            'emp_correo'=>$fiscales?->correo ?? 'xxxxx@xxxxxx.com','emp_telefono'=>$fiscales?->telefono ?? '0000000000'
        ];
    }
}
