<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter; margin: 12mm; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #0f172a; background: #f8fafc; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 14px 0 6px; color: #0f172a; }
        .muted { color: #64748b; }
        .header { background: #1a3347; color: #fff; padding: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-title { font-size: 18px; font-weight: 700; }
        .header-sub { font-size: 11px; color: rgba(255,255,255,.8); }
        .card { background: #ffffff; border: 1px solid #e2e8f0; padding: 12px; margin-top: 10px; }
        .section-title { background: #0f172a; color: #fff; padding: 6px 8px; font-size: 11px; font-weight: 700; }
        .pre { white-space: pre-wrap; line-height: 1.55; margin-top: 6px; }
        .kpi-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .kpi-table th, .kpi-table td { border: 1px solid #e2e8f0; padding: 6px 8px; }
        .kpi-table th { background: #f1f5f9; text-align: left; }
        .money { text-align: right; white-space: nowrap; }
        .bar { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-top: 4px; }
        .bar > span { display: block; height: 100%; background: linear-gradient(90deg, #13abcd, #29869e); }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: {{ $logo_width ?? 160 }}px;">
                    @if(!empty($logo_data))
                        <img src="{{ $logo_data }}" alt="Logo" style="height: 42px; max-width: {{ $logo_width ?? 160 }}px;">
                    @endif
                </td>
                <td>
                    <div class="header-title">Resumen Ejecutivo Financiero-Comercial</div>
                    <div class="header-sub">Reporte para Dirección General</div>
                </td>
                <td style="text-align:right;">
                    <div class="header-sub">Periodo {{ $registro->periodo }}</div>
                    <div class="header-sub">Generado {{ $registro->updated_at->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $datos = $registro->datos ?? [];
        $conversionComercial = (float) ($datos['conversion_comercial'] ?? 0);
        $conversionPonderada = (float) ($datos['conversion_ponderada'] ?? 0);
    @endphp

    <div class="card">
        <div class="section-title">Indicadores Clave</div>
        <table class="kpi-table">
            <thead>
                <tr>
                    <th>Indicador</th>
                    <th class="money">Valor</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Ventas del mes</td><td class="money">{{ number_format($datos['ventas_mes'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Ventas del año</td><td class="money">{{ number_format($datos['ventas_anio'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Utilidad del periodo</td><td class="money">{{ number_format($datos['utilidad_periodo'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Utilidad acumulada</td><td class="money">{{ number_format($datos['utilidad_acumulada'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Saldo bancos</td><td class="money">{{ number_format($datos['saldo_bancos'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Cuentas por cobrar</td><td class="money">{{ number_format($datos['cuentas_por_cobrar'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Cartera vencida</td><td class="money">{{ number_format($datos['cartera_vencida'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Cuentas por pagar</td><td class="money">{{ number_format($datos['cuentas_por_pagar'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Inventario</td><td class="money">{{ number_format($datos['inventario'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Facturación</td><td class="money">{{ number_format($datos['facturacion'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Monto cotizado</td><td class="money">{{ number_format($datos['monto_cotizado'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr><td>Ventas directas</td><td class="money">{{ number_format($datos['ventas_directas'] ?? 0, 2) }}</td><td class="muted">MXN</td></tr>
                <tr>
                    <td>Conversión comercial</td>
                    <td class="money">{{ number_format($conversionComercial, 1) }}%</td>
                    <td>
                        <div class="bar"><span style="width: {{ min(100, max(0, $conversionComercial)) }}%"></span></div>
                    </td>
                </tr>
                <tr>
                    <td>Conversión ponderada</td>
                    <td class="money">{{ number_format($conversionPonderada, 1) }}%</td>
                    <td>
                        <div class="bar"><span style="width: {{ min(100, max(0, $conversionPonderada)) }}%"></span></div>
                    </td>
                </tr>
                <tr><td>Ciclo promedio (días)</td><td class="money">{{ number_format($datos['ciclo_comercial_dias'] ?? 0, 1) }}</td><td class="muted">Días</td></tr>
            </tbody>
        </table>
    </div>

    @if($secciones)
        @foreach($secciones as $titulo => $contenido)
            <div class="card">
                <div class="section-title">{{ $titulo }}</div>
                <div class="pre">{{ trim($contenido) ?: 'Sin contenido generado.' }}</div>
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="section-title">Reporte</div>
            <div class="pre">{{ $registro->reporte }}</div>
        </div>
    @endif
</body>
</html>
