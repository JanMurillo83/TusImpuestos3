<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de Cambios en el Capital Contable</title>
    <style>
        @media print {
            html, body {
                -webkit-print-color-adjust: exact;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
            background-color: #FFC000;
            color: #000;
            padding: 8px;
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #000;
        }
        th.concepto {
            text-align: left;
        }
        td {
            padding: 5px 8px;
            border: 1px solid #ccc;
            text-align: right;
        }
        td.concepto {
            text-align: left;
            font-weight: bold;
        }
        .saldo-inicial {
            background-color: #FFF2CC;
        }
        .movimiento {
            background-color: #FFFFFF;
        }
        .saldo-final {
            background-color: #FFD966;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa_nombre ?? "Empresa" }}</h2>
        <h2>ESTADO DE CAMBIOS EN EL CAPITAL CONTABLE</h2>
        <h3>Conforme a las Normas de Información Financiera (NIF B-4)</h3>
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
                <th class="concepto">CONCEPTO</th>
                <th>Capital Social</th>
                <th>Aportaciones para Futuros Aumentos</th>
                <th>Prima en Emisión de Acciones</th>
                <th>Utilidades Retenidas</th>
                <th>Reserva Legal</th>
                <th>Resultado del Ejercicio</th>
                <th>Total Capital Contable</th>
            </tr>
        </thead>
        <tbody>
            <!-- SALDO INICIAL -->
            <tr class="saldo-inicial">
                <td class="concepto">Saldo al {{ $fecha_inicial ?? date("d/m/Y") }}</td>
                <td>{{ number_format($capital_social_inicial ?? 0, 2) }}</td>
                <td>{{ number_format($aportaciones_inicial ?? 0, 2) }}</td>
                <td>{{ number_format($prima_acciones_inicial ?? 0, 2) }}</td>
                <td>{{ number_format($utilidades_retenidas_inicial ?? 0, 2) }}</td>
                <td>{{ number_format($reserva_legal_inicial ?? 0, 2) }}</td>
                <td>{{ number_format($resultado_anterior ?? 0, 2) }}</td>
                <td>{{ number_format($total_capital_inicial ?? 0, 2) }}</td>
            </tr>

            <!-- MOVIMIENTOS DEL PERIODO -->
            <tr class="movimiento">
                <td class="concepto" colspan="8" style="background-color: #F2F2F2; text-align: center; font-weight: bold;">
                    MOVIMIENTOS DEL PERIODO
                </td>
            </tr>

            @if(($aportaciones_periodo ?? 0) != 0)
            <tr class="movimiento">
                <td class="concepto">Aportaciones de capital</td>
                <td>{{ number_format($aportaciones_periodo ?? 0, 2) }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format($aportaciones_periodo ?? 0, 2) }}</td>
            </tr>
            @endif

            @if(($capitalizacion_utilidades ?? 0) != 0)
            <tr class="movimiento">
                <td class="concepto">Capitalización de utilidades</td>
                <td>{{ number_format($capitalizacion_utilidades ?? 0, 2) }}</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format(-$capitalizacion_utilidades, 2) }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
            @endif

            @if(($reserva_periodo ?? 0) != 0)
            <tr class="movimiento">
                <td class="concepto">Traspaso a reserva legal</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format(-$reserva_periodo, 2) }}</td>
                <td>{{ number_format($reserva_periodo ?? 0, 2) }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
            @endif

            @if(($dividendos_decretados ?? 0) != 0)
            <tr class="movimiento">
                <td class="concepto">Dividendos decretados</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format(-$dividendos_decretados, 2) }}</td>
                <td>-</td>
                <td>-</td>
                <td class="negativo">{{ number_format(-$dividendos_decretados, 2) }}</td>
            </tr>
            @endif

            @if(($reembolsos_capital ?? 0) != 0)
            <tr class="movimiento">
                <td class="concepto">Reembolsos de capital</td>
                <td>{{ number_format(-$reembolsos_capital, 2) }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="negativo">{{ number_format(-$reembolsos_capital, 2) }}</td>
            </tr>
            @endif

            <tr class="movimiento">
                <td class="concepto">Traspaso del resultado del ejercicio anterior</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format($resultado_anterior ?? 0, 2) }}</td>
                <td>-</td>
                <td class="negativo">{{ number_format(-($resultado_anterior ?? 0), 2) }}</td>
                <td>-</td>
            </tr>

            <tr class="movimiento">
                <td class="concepto">Resultado del ejercicio {{ $ejercicio ?? date("Y") }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="{{ ($resultado_ejercicio ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($resultado_ejercicio ?? 0, 2) }}
                </td>
                <td class="{{ ($resultado_ejercicio ?? 0) >= 0 ? 'positivo' : 'negativo' }}">
                    {{ number_format($resultado_ejercicio ?? 0, 2) }}
                </td>
            </tr>

            <!-- SALDO FINAL -->
            <tr class="saldo-final">
                <td class="concepto">Saldo al {{ $fecha_fin ?? date("d/m/Y") }}</td>
                <td>{{ number_format($capital_social_final ?? 0, 2) }}</td>
                <td>{{ number_format($aportaciones_final ?? 0, 2) }}</td>
                <td>{{ number_format($prima_acciones_final ?? 0, 2) }}</td>
                <td>{{ number_format($utilidades_retenidas_final ?? 0, 2) }}</td>
                <td>{{ number_format($reserva_legal_final ?? 0, 2) }}</td>
                <td>{{ number_format($resultado_ejercicio ?? 0, 2) }}</td>
                <td>{{ number_format($total_capital_final ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- NOTAS EXPLICATIVAS -->
    <div style="margin-top: 20px; padding: 10px; background-color: #F9F9F9; border: 1px solid #ccc;">
        <strong>NOTAS:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Capital Social:</strong> Representa las aportaciones de los accionistas.</li>
            <li><strong>Utilidades Retenidas:</strong> Utilidades de ejercicios anteriores no distribuidas.</li>
            <li><strong>Reserva Legal:</strong> Fondo de reserva conforme al Artículo 20 de la Ley General de Sociedades Mercantiles (mínimo 5% hasta alcanzar 20% del capital social).</li>
            <li><strong>Resultado del Ejercicio:</strong> Utilidad o pérdida del periodo actual.</li>
        </ul>
    </div>

    <div class="footer">
        <p>Este reporte ha sido elaborado conforme a las Normas de Información Financiera (NIF) vigentes en México.</p>
        <p>NIF B-4: Estado de Cambios en el Capital Contable</p>
    </div>
</body>
</html>
