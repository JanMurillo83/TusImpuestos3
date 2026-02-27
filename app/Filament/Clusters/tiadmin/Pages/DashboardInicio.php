<?php

namespace App\Filament\Clusters\tiadmin\Pages;

use App\Filament\Clusters\tiadmin;
use App\Http\Controllers\MainChartsController;
use App\Http\Controllers\ReportesController;
use App\Models\Compras;
use App\Models\ComercialMotivoPerdida;
use App\Models\Cotizaciones;
use App\Models\Facturas;
use App\Models\Inventario;
use App\Models\Movinventario;
use App\Models\Movinventarios;
use App\Models\Ordenes;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    private function computeKPIs(Collection $quotes, Collection $invoices): array
    {
        $totalInvoiced = (float) $invoices->sum('total');
        $invoicedFromQuotes = $invoices->filter(fn ($i) => !empty($i->cotizacion_id));
        $invoicedFromQuotesValue = (float) $invoicedFromQuotes->sum('total');
        $invoicedDirectValue = $totalInvoiced - $invoicedFromQuotesValue;

        $totalQuotes = $quotes->count();
        $totalQuoted = (float) $quotes->sum('total');

        $quoteIds = $quotes->pluck('id')->flip();
        $invoicedQuoteIds = $invoicedFromQuotes->pluck('cotizacion_id')->unique()->filter(fn ($id) => $quoteIds->has($id));
        $invoicedQuotesCount = $invoicedQuoteIds->count();

        $conversion = $totalQuotes > 0 ? ($invoicedQuotesCount / $totalQuotes) : 0;
        $weighted = $totalQuoted > 0 ? ($invoicedFromQuotesValue / $totalQuoted) : 0;

        $quoteDateMap = $quotes->mapWithKeys(fn ($q) => [$q->id => $q->fecha]);
        $cycles = $invoicedFromQuotes->map(function ($inv) use ($quoteDateMap) {
            $qDate = $quoteDateMap->get($inv->cotizacion_id);
            if (!$qDate) {
                return null;
            }
            $d1 = Carbon::parse($qDate);
            $d2 = Carbon::parse($inv->fecha);
            return max(0, $d1->diffInDays($d2));
        })->filter(fn ($x) => $x !== null);
        $avgCycle = $cycles->count() ? $cycles->avg() : 0;

        $avgDiscount = $quotes->count() ? $quotes->avg('descuento_pct') : 0;

        $paidWeighted = $totalInvoiced > 0
            ? $invoices->sum(fn ($i) => (float) $i->total * (float) ($i->cobranza_pct ?? 0)) / $totalInvoiced
            : 0;
        $marginWeighted = $totalInvoiced > 0
            ? $invoices->sum(fn ($i) => (float) $i->total * (float) ($i->margen_pct ?? 0)) / $totalInvoiced
            : 0;

        $openCount = $quotes->filter(fn ($q) => in_array($q->estado_comercial, ['OPEN', 'NEGOTIATION'], true))->count();
        $lostCount = $quotes->filter(fn ($q) => in_array($q->estado_comercial, ['LOST', 'EXPIRED'], true))->count();

        return [
            'total_quotes' => $totalQuotes,
            'total_quoted' => $totalQuoted,
            'total_invoiced' => $totalInvoiced,
            'invoiced_from_quotes_value' => $invoicedFromQuotesValue,
            'invoiced_direct_value' => $invoicedDirectValue,
            'invoiced_quotes_count' => $invoicedQuotesCount,
            'open_count' => $openCount,
            'lost_count' => $lostCount,
            'conversion' => $conversion,
            'weighted' => $weighted,
            'avg_cycle' => $avgCycle,
            'avg_discount' => $avgDiscount,
            'paid_pct_weighted' => $paidWeighted,
            'margin_pct_weighted' => $marginWeighted,
        ];
    }

    public function getViewData(): array
    {
        $teamId = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;
        $user = auth()->user();
        $sellerOnly = $user->hasRole(['ventas']) && ! $user->hasRole(['administrador', 'contador', 'compras']);

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $teamId);

        $inicioPeriodo = Carbon::create($ejercicio, $periodo, 1)->startOfMonth();
        $finPeriodo = Carbon::create($ejercicio, $periodo, 1)->endOfMonth();

        $quotesQuery = Cotizaciones::where('team_id', $teamId)
            ->whereBetween('fecha', [$inicioPeriodo, $finPeriodo]);
        if ($sellerOnly) {
            $quotesQuery->where(function ($q) use ($user) {
                $q->where('created_by_user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('created_by_user_id')
                            ->where('nombre_elaboro', $user->name);
                    });
            });
        }

        $cotizacionesPeriodo = (clone $quotesQuery)->sum('total');

        $cotizacionesPendientesQuery = Cotizaciones::where('team_id', $teamId)
            ->whereIn('estado', ['Activa', 'Parcial']);
        if ($sellerOnly) {
            $cotizacionesPendientesQuery->where(function ($q) use ($user) {
                $q->where('created_by_user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('created_by_user_id')
                            ->where('nombre_elaboro', $user->name);
                    });
            });
        }
        $cotizacionesPendientesImporte = $cotizacionesPendientesQuery->sum('total');
        $cotizacionesPendientesCount = $cotizacionesPendientesQuery->count();

        $invoicesQuery = Facturas::where('team_id', $teamId)
            ->whereBetween('fecha', [$inicioPeriodo, $finPeriodo]);
        if ($sellerOnly) {
            $invoicesQuery->where('created_by_user_id', $user->id);
        }

        $facturasTimbradasPeriodo = (clone $invoicesQuery)
            ->where('timbrado', 'SI')
            ->sum('total');

        $costoInventario = (float) Inventario::where('team_id', $teamId)
            ->selectRaw('COALESCE(SUM(p_costo * exist), 0) as importe')
            ->value('importe');

        $quotes = (clone $quotesQuery)->get([
            'id', 'fecha', 'total', 'created_by_user_id', 'nombre_elaboro',
            'estado_comercial', 'descuento_pct', 'motivo_perdida_id',
        ]);
        $invoices = (clone $invoicesQuery)->get([
            'id', 'fecha', 'total', 'cotizacion_id', 'created_by_user_id',
            'margen_pct', 'cobranza_pct',
        ]);

        $userIds = $quotes->pluck('created_by_user_id')
            ->merge($invoices->pluck('created_by_user_id'))
            ->filter()
            ->unique()
            ->values();
        $users = $userIds->isEmpty()
            ? collect()
            : User::whereIn('id', $userIds)->pluck('name', 'id');

        $motivosPerdida = ComercialMotivoPerdida::where('team_id', $teamId)
            ->pluck('nombre', 'id');

        $kpis = $this->computeKPIs($quotes, $invoices);

        $quoteIds = $quotes->pluck('id')->values();
        $invoiceByQuote = $quoteIds->isEmpty()
            ? collect()
            : Facturas::where('team_id', $teamId)
                ->whereIn('cotizacion_id', $quoteIds)
                ->pluck('id', 'cotizacion_id');

        $latestQuotes = $quotes->sortByDesc('fecha')->take(8)->map(function ($q) use ($users, $invoiceByQuote) {
            $sellerName = $q->created_by_user_id ? ($users[$q->created_by_user_id] ?? 'Sin vendedor') : ($q->nombre_elaboro ?: 'Sin vendedor');
            return [
                'id' => $q->id,
                'fecha' => $q->fecha,
                'total' => (float) $q->total,
                'seller' => $sellerName,
                'estado_comercial' => $q->estado_comercial ?? 'OPEN',
                'invoice_id' => $invoiceByQuote[$q->id] ?? null,
            ];
        });

        $lossReasons = $quotes->filter(fn ($q) => in_array($q->estado_comercial, ['LOST', 'EXPIRED'], true))
            ->groupBy(fn ($q) => $q->motivo_perdida_id ?: 'sin')
            ->map(function ($items, $key) use ($motivosPerdida) {
                $label = $key === 'sin' ? '(Sin motivo)' : ($motivosPerdida[$key] ?? '(Sin motivo)');
                return [
                    'motivo' => $label,
                    'count' => $items->count(),
                    'importe' => (float) $items->sum('total'),
                ];
            })
            ->values()
            ->sortByDesc('importe')
            ->take(6)
            ->values();

        $scoreboard = [];
        foreach ($quotes as $q) {
            $sellerId = $q->created_by_user_id ?: 0;
            $sellerName = $q->created_by_user_id ? ($users[$q->created_by_user_id] ?? 'Sin vendedor') : ($q->nombre_elaboro ?: 'Sin vendedor');
            $key = $sellerId . '|' . $sellerName;
            if (!isset($scoreboard[$key])) {
                $scoreboard[$key] = [
                    'seller' => $sellerName,
                    'seller_id' => $sellerId,
                    'quotes' => collect(),
                    'invoices' => collect(),
                ];
            }
            $scoreboard[$key]['quotes']->push($q);
        }
        foreach ($invoices as $i) {
            $sellerId = $i->created_by_user_id ?: 0;
            $sellerName = $i->created_by_user_id ? ($users[$i->created_by_user_id] ?? 'Sin vendedor') : 'Sin vendedor';
            $key = $sellerId . '|' . $sellerName;
            if (!isset($scoreboard[$key])) {
                $scoreboard[$key] = [
                    'seller' => $sellerName,
                    'seller_id' => $sellerId,
                    'quotes' => collect(),
                    'invoices' => collect(),
                ];
            }
            $scoreboard[$key]['invoices']->push($i);
        }
        $scoreboard = collect($scoreboard)->map(function ($row) {
            $metrics = $this->computeKPIs($row['quotes'], $row['invoices']);
            return array_merge($row, $metrics);
        })->sortByDesc('total_invoiced')->values();

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

        $productosCount = Inventario::where('team_id', $teamId)->count();
        $inventarioValuado = (float) Inventario::where('team_id', $teamId)
            ->selectRaw('COALESCE(SUM(p_costo * exist), 0) as importe')
            ->value('importe');

        $almacenesCount = 0;
        if (Schema::hasTable('almacenes')) {
            $almacenesCount = (int) DB::table('almacenes')->where('team_id', $teamId)->count();
        } elseif (Schema::hasTable('almacen')) {
            $almacenesCount = (int) DB::table('almacen')->where('team_id', $teamId)->count();
        }

        $movimientosCount = 0;
        if (Schema::hasTable('movinventarios')) {
            $movimientosCount = Movinventarios::where('team_id', $teamId)->count();
        } elseif (Schema::hasTable('movinventario')) {
            $movimientosCount = Movinventario::where('team_id', $teamId)->count();
        }

        $minColumn = null;
        foreach (['minimo', 'min', 'minimo_stock', 'min_stock'] as $col) {
            if (Schema::hasColumn('inventarios', $col)) {
                $minColumn = $col;
                break;
            }
        }
        $almacenColumn = null;
        foreach (['almacen', 'almacen_id'] as $col) {
            if (Schema::hasColumn('inventarios', $col)) {
                $almacenColumn = $col;
                break;
            }
        }

        $lowStock = collect();
        $lowStockQuery = Inventario::where('team_id', $teamId);
        if ($minColumn) {
            $lowStockQuery->whereColumn('exist', '<', $minColumn);
        } else {
            $lowStockQuery->where('exist', '<=', 0);
        }
        $lowStock = $lowStockQuery
            ->orderBy('exist')
            ->limit(10)
            ->get(['clave', 'descripcion', 'exist', $minColumn ?? DB::raw('0 as minimo'), $almacenColumn ?? DB::raw("'—' as almacen")]);

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
            'ordenes_pendientes' => $ordenesPendientes,
            'ordenes_pendientes_importe' => $ordenesPendientesImporte,
            'compras_periodo' => $comprasPeriodo,
            'utilidad_periodo' => $utilidadPeriodo,
            'kpis' => $kpis,
            'scoreboard' => $scoreboard,
            'loss_reasons' => $lossReasons,
            'latest_quotes' => $latestQuotes,
            'seller_only' => $sellerOnly,
            'productos_count' => $productosCount,
            'almacenes_count' => $almacenesCount,
            'movimientos_count' => $movimientosCount,
            'inventario_valuado' => $inventarioValuado,
            'low_stock' => $lowStock,
            'status_labels' => [
                'OPEN' => 'Abierta',
                'NEGOTIATION' => 'En negociación',
                'WON' => 'Facturada',
                'LOST' => 'Perdida',
                'EXPIRED' => 'Expirada',
            ],
        ];
    }
}
