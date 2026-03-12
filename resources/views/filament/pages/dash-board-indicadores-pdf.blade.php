<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter; margin: 12mm; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #0f172a; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .logo { width: 140px; }
        .title h1 { margin: 0; font-size: 18px; }
        .title p { margin: 2px 0 0 0; color: #475569; font-size: 11px; }
        .meta { text-align: right; font-size: 10px; color: #475569; }
        h2 { font-size: 12px; margin: 12px 0 6px 0; color: #1f2937; }
        h3 { font-size: 11px; margin: 8px 0 6px 0; color: #1f2937; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 4px 6px; }
        .table th { background: #f8fafc; text-align: left; color: #334155; }
        .money { text-align: right; white-space: nowrap; }
        .muted { color: #64748b; }
        .grid-2 { width: 100%; border-collapse: collapse; margin-top: 6px; }
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
        <td class="title">
            <h1>Dashboard general</h1>
            <p>Resumen ejecutivo del negocio</p>
        </td>
        <td class="meta">
            <div>Usuario: <strong>{{ $usuario }}</strong></div>
            <div>Fecha: {{ $fecha }}</div>
            <div>Periodo: <strong>{{ $mes_actual }} {{ $ejercicio }}</strong></div>
        </td>
    </tr>
</table>

<h2>Resumen general del negocio</h2>
<table class="table">
    <thead>
    <tr>
        <th>Indicador</th>
        <th class="money">Importe</th>
        <th>Detalle</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Ventas del mes</td>
        <td class="money">{{ '$'.number_format($ventas, 2) }}</td>
        <td class="muted">Periodo: {{ $mes_actual }} {{ $ejercicio }}</td>
    </tr>
    <tr>
        <td>Ventas del año</td>
        <td class="money">{{ '$'.number_format($ventas_anuales, 2) }}</td>
        <td class="muted">Ejercicio: {{ $ejercicio }}</td>
    </tr>
    <tr>
        <td>Cuentas por cobrar</td>
        <td class="money">{{ '$'.number_format($cobrar_importe, 2) }}</td>
        <td class="muted">Corte: {{ $fecha }}</td>
    </tr>
    <tr>
        <td>Cartera vencida</td>
        <td class="money">{{ '$'.number_format($importe_vencido, 2) }}</td>
        <td class="muted">
            @php
                $var1 = floatval($cobrar_importe);
                $var2 = floatval($importe_vencido);
                $porc = $var2 * 100 / max($var1, 1);
            @endphp
            {{ number_format($porc, 2).'%' }} de la cobranza
        </td>
    </tr>
    <tr>
        <td>Cuentas por pagar</td>
        <td class="money">{{ '$'.number_format($pagar_importe, 2) }}</td>
        <td class="muted">Corte: {{ $fecha }}</td>
    </tr>
    </tbody>
</table>

<h2>Utilidad e impuestos</h2>
<table class="table">
    <thead>
    <tr>
        <th>Concepto</th>
        <th class="money">Importe</th>
        <th>Detalle</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Inventario</td>
        <td class="money">{{ '$'.number_format($importe_inventario, 2) }}</td>
        <td class="muted">Valuación total</td>
    </tr>
    <tr>
        <td>Saldo actual de bancos</td>
        <td class="money">{{ '$'.number_format($saldo_bancos, 2) }}</td>
        <td class="muted">Corte: {{ $fecha }}</td>
    </tr>
    <tr>
        <td>Utilidad del periodo</td>
        <td class="money">{{ '$'.number_format($utilidad_importe, 2) }}</td>
        <td class="muted">{{ $mes_actual }} {{ $ejercicio }}</td>
    </tr>
    <tr>
        <td>Utilidad acumulada del ejercicio</td>
        <td class="money">{{ '$'.number_format($utilidad_ejercicio, 2) }}</td>
        <td class="muted">Enero - {{ $mes_actual }} {{ $ejercicio }}</td>
    </tr>
    <tr>
        <td>Impuesto anual</td>
        <td class="money">{{ '$'.number_format($impuesto_estimado, 2) }}</td>
        <td class="muted">Utilidad del ejercicio * 30%</td>
    </tr>
    <tr>
        <td>ISR propio (mensual)</td>
        <td class="money">{{ '$'.number_format($impuesto_mensual, 2) }}</td>
        <td class="muted">Estimado del mes</td>
    </tr>
    <tr>
        <td>IVA</td>
        <td class="money">{{ '$'.number_format($importe_iva, 2) }}</td>
        <td class="muted">Saldo IVA</td>
    </tr>
    <tr>
        <td>Retenciones</td>
        <td class="money">{{ '$'.number_format($importe_ret, 2) }}</td>
        <td class="muted">Saldo retenciones</td>
    </tr>
    </tbody>
</table>

<table class="grid-2">
    <tr>
        <td>
            <h3>Ventas del mes por cliente</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="money">Importe</th>
                    <th class="money">% mes</th>
                </tr>
                </thead>
                <tbody>
                @php $impt_mes = 0; @endphp
                @foreach($mes_ventas_data as $data)
                    @php
                        $impo = floatval($data->importe);
                        $porc = $impo * 100 / max($ventas, 1);
                        $impt_mes += $impo;
                    @endphp
                    <tr>
                        <td>{{ $data->concepto }}</td>
                        <td class="money">{{ '$'.number_format($data->importe, 2) }}</td>
                        <td class="money">{{ number_format($porc, 2).'%' }}</td>
                    </tr>
                @endforeach
                @php
                    $impt_mes_dif = $ventas - $impt_mes;
                    $porc_t = $impt_mes_dif * 100 / max($ventas, 1);
                @endphp
                <tr>
                    <td>Otros clientes</td>
                    <td class="money">{{ '$'.number_format($impt_mes_dif, 2) }}</td>
                    <td class="money">{{ number_format($porc_t, 2).'%' }}</td>
                </tr>
                </tbody>
            </table>
        </td>
        <td>
            <h3>Ventas del año - mejores clientes</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="money">Importe</th>
                    <th class="money">% año</th>
                </tr>
                </thead>
                <tbody>
                @php $impt_anio = 0; @endphp
                @foreach($anio_ventas_data as $data)
                    @php
                        $impo = floatval($data->importe);
                        $porc = $impo * 100 / max($ventas_anuales, 1);
                        $impt_anio += $impo;
                    @endphp
                    <tr>
                        <td>{{ $data->concepto }}</td>
                        <td class="money">{{ '$'.number_format($data->importe, 2) }}</td>
                        <td class="money">{{ number_format($porc, 2).'%' }}</td>
                    </tr>
                @endforeach
                @php
                    $impt_anio_dif = $ventas_anuales - $impt_anio;
                    $porc_t = $impt_anio_dif * 100 / max($ventas_anuales, 1);
                @endphp
                <tr>
                    <td>Otros clientes</td>
                    <td class="money">{{ '$'.number_format($impt_anio_dif, 2) }}</td>
                    <td class="money">{{ number_format($porc_t, 2).'%' }}</td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="grid-2">
    <tr>
        <td>
            <h3>Cuentas por cobrar</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="money">Saldo</th>
                    <th class="money">% cartera</th>
                </tr>
                </thead>
                <tbody>
                @php $impt3 = 0; @endphp
                @foreach($cuentas_x_cobrar_top3 as $cliente)
                    @php
                        $impt3 += floatval($cliente->saldo);
                        $porc = floatval($cliente->saldo) * 100 / max($cobrar_importe, 1);
                    @endphp
                    <tr>
                        <td>{{ $cliente->cliente }}</td>
                        <td class="money">{{ '$'.number_format($cliente->saldo, 2) }}</td>
                        <td class="money">{{ number_format($porc, 2).'%' }}</td>
                    </tr>
                @endforeach
                @php
                    $impt_o = $cobrar_importe - $impt3;
                    $porc_t = $impt_o * 100 / max($cobrar_importe, 1);
                @endphp
                <tr>
                    <td>Otros clientes</td>
                    <td class="money">{{ '$'.number_format($impt_o, 2) }}</td>
                    <td class="money">{{ number_format($porc_t, 2).'%' }}</td>
                </tr>
                </tbody>
            </table>
        </td>
        <td>
            <h3>Cuentas por pagar - proveedores</h3>
            <table class="table">
                <thead>
                <tr>
                    <th>Proveedor</th>
                    <th class="money">Saldo</th>
                    <th class="money">% CxP</th>
                </tr>
                </thead>
                <tbody>
                @php $imptp = 0; @endphp
                @foreach($cuentas_x_pagar_top3 as $proveedor)
                    @php
                        $imptp += floatval($proveedor->saldo);
                        $porc = floatval($proveedor->saldo) * 100 / max($pagar_importe, 1);
                    @endphp
                    <tr>
                        <td>{{ $proveedor->cliente }}</td>
                        <td class="money">{{ '$'.number_format($proveedor->saldo, 2) }}</td>
                        <td class="money">{{ number_format($porc, 2).'%' }}</td>
                    </tr>
                @endforeach
                @php
                    $impt_p = $pagar_importe - $imptp;
                    $porc_tp = $impt_p * 100 / max($pagar_importe, 1);
                @endphp
                <tr>
                    <td>Otros proveedores</td>
                    <td class="money">{{ '$'.number_format($impt_p, 2) }}</td>
                    <td class="money">{{ number_format($porc_tp, 2).'%' }}</td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<div class="footer">
    Contacto: {{ $emp_correo }} · {{ $emp_telefono }}
</div>
</body>
</html>
