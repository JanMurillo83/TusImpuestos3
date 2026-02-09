<?php

namespace App\Filament\Pages;

use App\Models\DatosFiscales;
use App\Models\Inventario;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use App\Http\Controllers\MainChartsController;

class InventarioDetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.inventario-detalle';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Detalle de Inventario';

    public function getTitle(): string
    {
        return '';
    }

    protected function getViewData(): array
    {
        $team_id = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        $empresa = Filament::getTenant()->name;

        $inventario_data = Inventario::where('team_id', $team_id)
            ->selectRaw('clave, descripcion, p_costo, exist, (p_costo * exist) as importe_total')
            ->orderBy('importe_total', 'desc')
            ->get();

        $costo_total = (float) Inventario::where('team_id', $team_id)
            ->selectRaw('COALESCE(SUM(p_costo * exist), 0) as importe')
            ->value('importe');

        $fiscales = DatosFiscales::where('team_id', $team_id)->first();

        return [
            'empresa' => $empresa,
            'team_id' => $team_id,
            'ejercicio' => $ejercicio,
            'periodo' => $periodo,
            'mes_letras' => $mes_letras,
            'inventario_data' => $inventario_data,
            'costo_total' => $costo_total,
            'emp_correo' => $fiscales?->correo ?? 'xxxxx@xxxxxx.com',
            'emp_telefono' => $fiscales?->telefono ?? '0000000000'
        ];
    }
}
