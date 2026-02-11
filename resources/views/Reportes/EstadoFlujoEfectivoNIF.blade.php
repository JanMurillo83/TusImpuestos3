<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de Flujos de Efectivo</title>
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
            background-color: #5B9BD5;
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
        .seccion {
            font-weight: bold;
            background-color: #DEEBF7;
            font-size: 12px;
            border-top: 2px solid #5B9BD5;
        }
        .nivel-1 {
            font-weight: bold;
            background-color: #F0F6FC;
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
            background-color: #BDD7EE;
            font-size: 11px;
        }
        .total {
            font-weight: bold;
            background-color: #9BC2E6;
            border-top: 2px solid #000;
            font-size: 12px;
        }
        .resultado-final {
            font-weight: bold;
            background-color: #5B9BD5;
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
        .entrada {
            color: #006100;
        }
        .salida {
            color: #C00000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa_nombre ?? "Empresa" }}</h2>
        <h2>ESTADO DE FLUJOS DE EFECTIVO</h2>
        <h3>Conforme a las Normas de Información Financiera (NIF B-2)</h3>
        <h3>Método Indirecto</h3>
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
                <th style="width: 70%">CONCEPTO</th>
                <th class="text-right" style="width: 30%">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            <!-- ACTIVIDADES DE OPERACIÓN -->
            <tr class="seccion">
                <td colspan="2">ACTIVIDADES DE OPERACIÓN</td>
            </tr>

            <tr class="nivel-1">
                <td>Utilidad (pérdida) neta del ejercicio</td>
                <td class="text-right {{ ($utilidad_neta ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($utilidad_neta ?? 0, 2) }}
                </td>
            </tr>

            <tr class="nivel-1">
                <td colspan="2" style="font-style: italic; padding-top: 10px;">Ajustes por partidas que no implican flujo de efectivo:</td>
            </tr>

            @foreach(($ajustes_operacion ?? []) as $ajuste)
            <tr class="nivel-2">
                <td>{{ $ajuste->concepto }}</td>
                <td class="text-right">{{ number_format($ajuste->importe, 2) }}</td>
            </tr>
            @endforeach

            <tr class="nivel-1" style="padding-top: 10px;">
                <td colspan="2" style="font-style: italic;">Cambios en activos y pasivos de operación:</td>
            </tr>

            @foreach(($cambios_operacion ?? []) as $cambio)
            <tr class="nivel-2">
                <td>{{ $cambio->concepto }}</td>
                <td class="text-right {{ $cambio->importe >= 0 ? 'entrada' : 'salida' }}">
                    {{ number_format($cambio->importe, 2) }}
                </td>
            </tr>
            @endforeach

            <tr class="subtotal">
                <td>Flujo de efectivo generado por (utilizado en) actividades de operación</td>
                <td class="text-right {{ ($flujo_operacion ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($flujo_operacion ?? 0, 2) }}
                </td>
            </tr>

            <!-- ACTIVIDADES DE INVERSIÓN -->
            <tr class="seccion">
                <td colspan="2">ACTIVIDADES DE INVERSIÓN</td>
            </tr>

            @foreach(($actividades_inversion ?? []) as $actividad)
            <tr class="nivel-2">
                <td>{{ $actividad->concepto }}</td>
                <td class="text-right {{ $actividad->importe >= 0 ? 'entrada' : 'salida' }}">
                    {{ number_format($actividad->importe, 2) }}
                </td>
            </tr>
            @endforeach

            <tr class="subtotal">
                <td>Flujo de efectivo generado por (utilizado en) actividades de inversión</td>
                <td class="text-right {{ ($flujo_inversion ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($flujo_inversion ?? 0, 2) }}
                </td>
            </tr>

            <!-- ACTIVIDADES DE FINANCIAMIENTO -->
            <tr class="seccion">
                <td colspan="2">ACTIVIDADES DE FINANCIAMIENTO</td>
            </tr>

            @foreach(($actividades_financiamiento ?? []) as $actividad)
            <tr class="nivel-2">
                <td>{{ $actividad->concepto }}</td>
                <td class="text-right {{ $actividad->importe >= 0 ? 'entrada' : 'salida' }}">
                    {{ number_format($actividad->importe, 2) }}
                </td>
            </tr>
            @endforeach

            <tr class="subtotal">
                <td>Flujo de efectivo generado por (utilizado en) actividades de financiamiento</td>
                <td class="text-right {{ ($flujo_financiamiento ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($flujo_financiamiento ?? 0, 2) }}
                </td>
            </tr>

            <!-- INCREMENTO/DISMINUCIÓN NETA -->
            <tr class="total">
                <td>INCREMENTO (DISMINUCIÓN) NETO EN EFECTIVO Y EQUIVALENTES</td>
                <td class="text-right {{ ($incremento_neto ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($incremento_neto ?? 0, 2) }}
                </td>
            </tr>

            <!-- EFECTIVO INICIAL Y FINAL -->
            <tr class="nivel-1" style="border-top: 2px solid #000; margin-top: 10px;">
                <td>Efectivo y equivalentes al inicio del periodo</td>
                <td class="text-right">{{ number_format($efectivo_inicial ?? 0, 2) }}</td>
            </tr>

            <tr class="resultado-final">
                <td>EFECTIVO Y EQUIVALENTES AL FINAL DEL PERIODO</td>
                <td class="text-right">{{ number_format($efectivo_final ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- CONCILIACIÓN -->
    <div style="margin-top: 20px; padding: 10px; background-color: #F0F6FC; border: 2px solid #5B9BD5;">
        <strong>CONCILIACIÓN:</strong>
        <table style="border: none; margin-top: 10px;">
            <tr>
                <td style="border: none;">Efectivo y equivalentes al inicio</td>
                <td style="border: none; text-align: right;">{{ number_format($efectivo_inicial ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="border: none;">(+) Incremento neto del periodo</td>
                <td style="border: none; text-align: right;">{{ number_format($incremento_neto ?? 0, 2) }}</td>
            </tr>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td style="border: none;">Efectivo y equivalentes al final</td>
                <td style="border: none; text-align: right;">{{ number_format($efectivo_final ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- NOTAS EXPLICATIVAS -->
    <div style="margin-top: 20px; padding: 10px; background-color: #F9F9F9; border: 1px solid #ccc;">
        <strong>NOTAS:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Método Indirecto:</strong> Parte de la utilidad neta y la ajusta por partidas que no requieren efectivo.</li>
            <li><strong>Actividades de Operación:</strong> Flujos relacionados con la actividad principal del negocio.</li>
            <li><strong>Actividades de Inversión:</strong> Adquisición y disposición de activos de largo plazo.</li>
            <li><strong>Actividades de Financiamiento:</strong> Cambios en capital y préstamos de la entidad.</li>
            <li><strong>Efectivo y Equivalentes:</strong> Incluye caja, bancos y valores de fácil realización.</li>
        </ul>
    </div>

    <div class="footer">
        <p>Este reporte ha sido elaborado conforme a las Normas de Información Financiera (NIF) vigentes en México.</p>
        <p>NIF B-2: Estado de Flujos de Efectivo</p>
    </div>
</body>
</html>
