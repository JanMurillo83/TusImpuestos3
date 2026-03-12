<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter; margin: 12mm; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 12px; margin: 14px 0 6px; color: #1f2937; }
        h3 { font-size: 11px; margin: 10px 0 6px; color: #1f2937; }
        .muted { color: #64748b; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 150px; }
        .meta { text-align: right; font-size: 10px; color: #475569; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 4px 6px; }
        .table th { background: #f8fafc; text-align: left; color: #334155; }
        .money { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .grid-2 { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .grid-2 td { width: 50%; vertical-align: top; padding-right: 6px; }
        .grid-2 td:last-child { padding-right: 0; padding-left: 6px; }
        .footer { margin-top: 10px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td class="logo">
            @if(!empty($logo_data))
                <img src="{{ $logo_data }}" alt="Logo" style="height: 40px;">
            @endif
        </td>
        <td>
            <h1>Resumen Ejecutivo Comercial</h1>
            <div class="muted">Dashboard · Cotizaciones · Pipeline · Facturas · Vendedores</div>
        </td>
        <td class="meta">
            <div>Usuario: <strong>{{ $payload['auth']['userName'] ?? '' }}</strong></div>
            <div>Periodo: {{ $payload['period']['label'] ?? '' }}</div>
            <div>Rango: {{ $payload['period']['from'] ?? '' }} a {{ $payload['period']['to'] ?? '' }}</div>
            <div>Generado: {{ now()->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

@php
    $filterSeller = $filters['sellerId'] === 'ALL' ? 'Todos' : ($maps['sellers'][$filters['sellerId']] ?? $filters['sellerId']);
    $filterChannel = $filters['channel'] === 'ALL' ? 'Todos' : ($maps['channels'][$filters['channel']] ?? $filters['channel']);
    $filterSegment = $filters['segment'] === 'ALL' ? 'Todos' : ($maps['segments'][$filters['segment']] ?? $filters['segment']);
@endphp

<div class="muted" style="margin-bottom: 6px;">
    Filtros: Vendedor {{ $filterSeller }} · Canal {{ $filterChannel }} · Segmento {{ $filterSegment }}
</div>

<h2>KPIs principales</h2>
<table class="table">
    <thead>
    <tr>
        <th>Indicador</th>
        <th class="money">Valor</th>
        <th>Detalle</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Cotizaciones</td>
        <td class="money">{{ $kpis['totalQuotes'] }}</td>
        <td class="muted">{{ $kpis['openCount'] }} abiertas · {{ $kpis['invoicedQuotesCount'] }} facturadas · {{ $kpis['lostCount'] }} perdidas</td>
    </tr>
    <tr>
        <td>$ Cotizado</td>
        <td class="money">{{ '$'.number_format($kpis['totalQuoted'], 2) }}</td>
        <td class="muted">Base para conversión ponderada</td>
    </tr>
    <tr>
        <td>$ Facturado total</td>
        <td class="money">{{ '$'.number_format($kpis['totalInvoiced'], 2) }}</td>
        <td class="muted">{{ '$'.number_format($kpis['invoicedFromQuotesValue'], 2) }} desde cotizaciones</td>
    </tr>
    <tr>
        <td>Ventas directas</td>
        <td class="money">{{ '$'.number_format($kpis['invoicedDirectValue'], 2) }}</td>
        <td class="muted">Facturas sin cotización</td>
    </tr>
    <tr>
        <td>Conversión</td>
        <td class="money">{{ number_format($kpis['conversion'] * 100, 1) }}%</td>
        <td class="muted">Facturadas / totales</td>
    </tr>
    <tr>
        <td>Conversión ponderada</td>
        <td class="money">{{ number_format($kpis['weighted'] * 100, 1) }}%</td>
        <td class="muted">$ Facturado / $ Cotizado</td>
    </tr>
    <tr>
        <td>Ciclo promedio</td>
        <td class="money">{{ number_format($kpis['avgCycle'], 1) }} días</td>
        <td class="muted">De cotización a factura</td>
    </tr>
    <tr>
        <td>Descuento promedio</td>
        <td class="money">{{ number_format($kpis['avgDiscount'], 1) }}%</td>
        <td class="muted">Control comercial</td>
    </tr>
    <tr>
        <td>Margen ponderado</td>
        <td class="money">{{ number_format($kpis['marginPctWeighted'] * 100, 1) }}%</td>
        <td class="muted">Sobre facturado</td>
    </tr>
    <tr>
        <td>Cobranza ponderada</td>
        <td class="money">{{ number_format($kpis['paidPctWeighted'] * 100, 1) }}%</td>
        <td class="muted">% cobrado sobre facturado</td>
    </tr>
    </tbody>
</table>

<table class="grid-2">
    <tr>
        <td>
            <h2>Pipeline por estatus</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Estatus</th>
                    <th class="center">#</th>
                    <th class="money">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($pipeline['byStatus'] as $row)
                    <tr>
                        <td>{{ $row['title'] }}</td>
                        <td class="center">{{ $row['count'] }}</td>
                        <td class="money">{{ '$'.number_format($row['total'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
        <td>
            <h2>Aging de cotizaciones abiertas</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Rango</th>
                    <th class="center">#</th>
                </tr>
                </thead>
                <tbody>
                @foreach($pipeline['aging'] as $row)
                    <tr>
                        <td>{{ $row['label'] }} días</td>
                        <td class="center">{{ $row['count'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="grid-2">
    <tr>
        <td>
            <h2>Motivos principales de pérdida</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Motivo</th>
                    <th class="center">#</th>
                    <th class="money">Monto</th>
                </tr>
                </thead>
                <tbody>
                @forelse(array_slice($lossReasons, 0, 6) as $row)
                    <tr>
                        <td>{{ $row['reason'] }}</td>
                        <td class="center">{{ $row['count'] }}</td>
                        <td class="money">{{ '$'.number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Sin pérdidas registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
        <td>
            <h2>Últimas cotizaciones</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Folio</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Estatus</th>
                    <th class="money">Monto</th>
                </tr>
                </thead>
                <tbody>
                @foreach($latestQuotes as $q)
                    <tr>
                        <td>{{ $q['id'] ?? '' }}</td>
                        <td>{{ $q['client'] ?? '' }}</td>
                        <td>{{ $q['sellerLabel'] ?? '' }}</td>
                        <td>{{ $q['statusLabel'] ?? '' }}</td>
                        <td class="money">{{ '$'.number_format($q['total'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
    </tr>
</table>

<h2>Facturas recientes</h2>
<table class="table">
    <thead>
    <tr>
        <th>Factura</th>
        <th>Cliente</th>
        <th>Vendedor</th>
        <th>Origen</th>
        <th>Fecha</th>
        <th class="money">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($topInvoices as $inv)
        @php $origin = !empty($inv['quoteId']) ? ('Cotización '.$inv['quoteId']) : 'Directa'; @endphp
        <tr>
            <td>{{ $inv['id'] ?? '' }}</td>
            <td>{{ $inv['client'] ?? '' }}</td>
            <td>{{ $inv['sellerLabel'] ?? '' }}</td>
            <td>{{ $origin }}</td>
            <td>{{ $inv['issuedAt'] ?? '' }}</td>
            <td class="money">{{ '$'.number_format($inv['total'] ?? 0, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Vendedores</h2>
<table class="table">
    <thead>
    <tr>
        <th>Vendedor</th>
        <th class="center">Cotizaciones</th>
        <th class="money">$ Facturado</th>
        <th class="money">Conversión</th>
        <th class="money">Ponderada</th>
        <th class="money">Ciclo</th>
        <th class="money">Margen</th>
        <th class="money">Cobranza</th>
    </tr>
    </thead>
    <tbody>
    @foreach($vendors as $row)
        <tr>
            <td>{{ $row['seller'] }}</td>
            <td class="center">{{ $row['quotes'] }}</td>
            <td class="money">{{ '$'.number_format($row['kpis']['totalInvoiced'], 2) }}</td>
            <td class="money">{{ number_format($row['kpis']['conversion'] * 100, 1) }}%</td>
            <td class="money">{{ number_format($row['kpis']['weighted'] * 100, 1) }}%</td>
            <td class="money">{{ number_format($row['kpis']['avgCycle'], 1) }} d</td>
            <td class="money">{{ number_format($row['kpis']['marginPctWeighted'] * 100, 1) }}%</td>
            <td class="money">{{ number_format($row['kpis']['paidPctWeighted'] * 100, 1) }}%</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    Resumen ejecutivo comercial
</div>
</body>
</html>
