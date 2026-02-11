<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de Resultados Integral</title>
    <style>
        @media print {
            html, body {
                -webkit-print-color-adjust: exact;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }
        .header h3 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #70AD47;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            padding: 5px 8px;
            border-bottom: 1px solid #ddd;
        }
        .nivel-1 {
            font-weight: bold;
            background-color: #E2EFDA;
        }
        .nivel-2 {
            padding-left: 15px;
        }
        .nivel-3 {
            padding-left: 30px;
            font-size: 10px;
        }
        .subtotal {
            font-weight: bold;
            background-color: #C6E0B4;
            font-size: 11px;
        }
        .total {
            font-weight: bold;
            background-color: #A9D08E;
            border-top: 2px solid #000;
            font-size: 12px;
        }
        .utilidad-neta {
            font-weight: bold;
            background-color: #70AD47;
            color: white;
            border-top: 3px double #000;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }
        .positivo {
            color: #006100;
        }
        .negativo {
            color: #C00000;
        }
        .porcentaje {
            font-size: 10px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa_nombre ?? "Empresa" }}</h2>
        <h2>ESTADO DE RESULTADOS INTEGRAL</h2>
        <h3>Conforme a las Normas de Información Financiera (NIF B-3)</h3>
        <h3>Del {{ $fecha_inicio ?? date("d/m/Y") }} al {{ $fecha_fin ?? date("d/m/Y") }}</h3>
        <h3>Cifras expresadas en pesos mexicanos</h3>
    </div>

    <div class="info-section">
        <div>RFC: {{ $rfc ?? "" }}</div>
        <div>Periodo: {{ $periodo ?? "" }}/{{ $ejercicio ?? date("Y") }}</div>
        <div>Fecha de emisión: {{ $fecha_emision ?? date("d/m/Y") }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%">CONCEPTO</th>
                <th class="text-right" style="width: 25%">PERIODO {{ $periodo ?? "" }}/{{ $ejercicio ?? date("Y") }}</th>
                <th class="text-right" style="width: 25%">ACUMULADO {{ $ejercicio ?? date("Y") }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- INGRESOS -->
            <tr class="nivel-1">
                <td>INGRESOS</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($ingresos ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_periodo, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_acumulado, 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td>INGRESOS NETOS</td>
                <td class="text-right">{{ number_format($total_ingresos_periodo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_ingresos_acumulado ?? 0, 2) }}</td>
            </tr>

            <!-- COSTO DE VENTAS -->
            <tr class="nivel-1">
                <td>COSTO DE VENTAS</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($costos ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_periodo, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_acumulado, 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td>TOTAL COSTO DE VENTAS</td>
                <td class="text-right">{{ number_format($total_costos_periodo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_costos_acumulado ?? 0, 2) }}</td>
            </tr>

            <!-- UTILIDAD BRUTA -->
            <tr class="total">
                <td>UTILIDAD BRUTA</td>
                <td class="text-right {{ ($utilidad_bruta_periodo ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_bruta_periodo ?? 0, 2) }}
                    <span class="porcentaje">({{ number_format((($utilidad_bruta_periodo ?? 0) / max(($total_ingresos_periodo ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
                <td class="text-right {{ ($utilidad_bruta_acumulado ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_bruta_acumulado ?? 0, 2) }}
                    <span class="porcentaje">({{ number_format((($utilidad_bruta_acumulado ?? 0) / max(($total_ingresos_acumulado ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
            </tr>

            <!-- GASTOS DE OPERACIÓN -->
            <tr class="nivel-1">
                <td>GASTOS DE OPERACIÓN</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($gastos_operacion ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_periodo, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_acumulado, 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td>TOTAL GASTOS DE OPERACIÓN</td>
                <td class="text-right">{{ number_format($total_gastos_periodo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_gastos_acumulado ?? 0, 2) }}</td>
            </tr>

            <!-- UTILIDAD DE OPERACIÓN -->
            <tr class="total">
                <td>UTILIDAD DE OPERACIÓN</td>
                <td class="text-right {{ ($utilidad_operacion_periodo ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_operacion_periodo ?? 0, 2) }}
                    <span class="porcentaje">({{ number_format((($utilidad_operacion_periodo ?? 0) / max(($total_ingresos_periodo ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
                <td class="text-right {{ ($utilidad_operacion_acumulado ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_operacion_acumulado ?? 0, 2) }}
                    <span class="porcentaje">({{ number_format((($utilidad_operacion_acumulado ?? 0) / max(($total_ingresos_acumulado ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
            </tr>

            <!-- RESULTADO INTEGRAL DE FINANCIAMIENTO -->
            <tr class="nivel-1">
                <td>RESULTADO INTEGRAL DE FINANCIAMIENTO</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($financiamiento ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_periodo, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_acumulado, 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td>TOTAL RESULTADO INTEGRAL DE FINANCIAMIENTO</td>
                <td class="text-right {{ ($total_financiamiento_periodo ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($total_financiamiento_periodo ?? 0, 2) }}
                </td>
                <td class="text-right {{ ($total_financiamiento_acumulado ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($total_financiamiento_acumulado ?? 0, 2) }}
                </td>
            </tr>

            <!-- OTROS INGRESOS Y GASTOS -->
            @if(count($otros_resultados ?? []) > 0)
            <tr class="nivel-1">
                <td>OTROS INGRESOS Y GASTOS</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($otros_resultados ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_periodo, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_acumulado, 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td>TOTAL OTROS INGRESOS Y GASTOS</td>
                <td class="text-right">{{ number_format($total_otros_periodo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_otros_acumulado ?? 0, 2) }}</td>
            </tr>
            @endif

            <!-- UTILIDAD ANTES DE IMPUESTOS -->
            <tr class="total">
                <td>UTILIDAD ANTES DE IMPUESTOS</td>
                <td class="text-right {{ ($utilidad_antes_impuestos_periodo ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_antes_impuestos_periodo ?? 0, 2) }}
                    <span class="porcentaje">({{ number_format((($utilidad_antes_impuestos_periodo ?? 0) / max(($total_ingresos_periodo ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
                <td class="text-right {{ ($utilidad_antes_impuestos_acumulado ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_antes_impuestos_acumulado ?? 0, 2) }}
                    <span class="porcentaje">({{ number_format((($utilidad_antes_impuestos_acumulado ?? 0) / max(($total_ingresos_acumulado ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
            </tr>

            <!-- IMPUESTOS -->
            <tr class="nivel-1">
                <td>IMPUESTOS A LA UTILIDAD</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($impuestos ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_periodo, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_acumulado, 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal">
                <td>TOTAL IMPUESTOS</td>
                <td class="text-right">{{ number_format($total_impuestos_periodo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_impuestos_acumulado ?? 0, 2) }}</td>
            </tr>

            <!-- UTILIDAD NETA -->
            <tr class="utilidad-neta">
                <td>UTILIDAD NETA DEL EJERCICIO</td>
                <td class="text-right">
                    {{ number_format($utilidad_neta_periodo ?? 0, 2) }}
                    <span class="porcentaje" style="color: white;">({{ number_format((($utilidad_neta_periodo ?? 0) / max(($total_ingresos_periodo ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
                <td class="text-right">
                    {{ number_format($utilidad_neta_acumulado ?? 0, 2) }}
                    <span class="porcentaje" style="color: white;">({{ number_format((($utilidad_neta_acumulado ?? 0) / max(($total_ingresos_acumulado ?? 1), 1)) * 100, 1) }}%)</span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Este reporte ha sido elaborado conforme a las Normas de Información Financiera (NIF) vigentes en México.</p>
        <p>NIF B-3: Estado de Resultados y Otros Resultados Integrales</p>
        <p>Los porcentajes mostrados representan el margen sobre ingresos netos.</p>
    </div>
</body>
</html>
