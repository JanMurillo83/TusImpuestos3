<?php

namespace App\Filament\Clusters\tiadmin\Pages;

use App\Filament\Clusters\tiadmin;
use App\Http\Controllers\MainChartsController;
use App\Http\Controllers\ReportesController;
use App\Models\Compras;
use App\Models\Cotizaciones;
use App\Models\Facturas;
use App\Models\Inventario;
use App\Models\Ordenes;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class DashboardInicio extends Page
{
    protected static ?string $navigationIcon = 'fas-chart-line';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?int $navigationSort = 0;
    protected static ?string $slug = 'inicio';
    protected ?string $maxContentWidth = 'full';

    protected static string $view = 'filament.clusters.tiadmin.pages.dashboard-inicio';

    public function getTitle(): string
    {
        return '';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras', 'ventas']);
    }

    public function getViewData(): array
    {
        $teamId = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $teamId);

        $inicioPeriodo = Carbon::create($ejercicio, $periodo, 1)->startOfMonth();
        $finPeriodo = Carbon::create($ejercicio, $periodo, 1)->endOfMonth();

        $cotizacionesPeriodo = Cotizaciones::where('team_id', $teamId)
            ->whereBetween('fecha', [$inicioPeriodo, $finPeriodo])
            ->sum('total');

        $cotizacionesPendientesQuery = Cotizaciones::where('team_id', $teamId)
            ->whereIn('estado', ['Activa', 'Parcial']);
        $cotizacionesPendientesImporte = $cotizacionesPendientesQuery->sum('total');
        $cotizacionesPendientesCount = $cotizacionesPendientesQuery->count();

        $facturasTimbradasPeriodo = Facturas::where('team_id', $teamId)
            ->where('timbrado', 'SI')
            ->whereBetween('fecha', [$inicioPeriodo, $finPeriodo])
            ->sum('total');

        $costoInventario = (float) Inventario::where('team_id', $teamId)
            ->selectRaw('COALESCE(SUM(p_costo * exist), 0) as importe')
            ->value('importe');

        $cotizacionesVendedor = Cotizaciones::where('team_id', $teamId)
            ->whereBetween('fecha', [$inicioPeriodo, $finPeriodo])
            ->selectRaw('vendedor, COALESCE(SUM(total), 0) as importe')
            ->groupBy('vendedor')
            ->orderBy('importe', 'desc')
            ->get();

        $vendedores = User::whereIn('id', $cotizacionesVendedor->pluck('vendedor')->filter()->all())
            ->get()
            ->keyBy('id');

        $cotizacionesPorVendedor = $cotizacionesVendedor->map(function ($row) use ($vendedores) {
            $nombre = $row->vendedor && $vendedores->has($row->vendedor)
                ? $vendedores[$row->vendedor]->name
                : 'Sin vendedor';
            return [
                'nombre' => $nombre,
                'importe' => (float) $row->importe,
            ];
        });

        $ordenesPendientes = Ordenes::where('team_id', $teamId)
            ->whereIn('estado', ['Activa', 'Parcial'])
            ->count();
        $ordenesPendientesImporte = Ordenes::where('team_id', $teamId)
            ->whereIn('estado', ['Activa', 'Parcial'])
            ->sum('total');

        $comprasPeriodo = Compras::where('team_id', $teamId)
            ->whereBetween('fecha', [$inicioPeriodo, $finPeriodo])
            ->where('estado', '!=', 'Cancelada')
            ->sum('total');

        $utilidadPeriodo = app(MainChartsController::class)->GetUtiPer($teamId);

        return [
            'team_id' => $teamId,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'mes_actual' => app(MainChartsController::class)->mes_letras($periodo),
            'fecha' => Carbon::now()->format('d/m/Y'),
            'cotizaciones_periodo' => $cotizacionesPeriodo,
            'cotizaciones_pendientes_importe' => $cotizacionesPendientesImporte,
            'cotizaciones_pendientes_count' => $cotizacionesPendientesCount,
            'facturas_timbradas_periodo' => $facturasTimbradasPeriodo,
            'costo_inventario' => $costoInventario,
            'cotizaciones_por_vendedor' => $cotizacionesPorVendedor,
            'ordenes_pendientes' => $ordenesPendientes,
            'ordenes_pendientes_importe' => $ordenesPendientesImporte,
            'compras_periodo' => $comprasPeriodo,
            'utilidad_periodo' => $utilidadPeriodo,
        ];
    }
}
