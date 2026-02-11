<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balance General (Estado de Situación Financiera)</title>
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
            background-color: #4472C4;
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
            background-color: #D9E2F3;
        }
        .nivel-2 {
            padding-left: 15px;
        }
        .nivel-3 {
            padding-left: 30px;
            font-size: 10px;
        }
        .total {
            font-weight: bold;
            background-color: #F2F2F2;
            border-top: 2px solid #000;
        }
        .total-section {
            font-weight: bold;
            background-color: #BDD7EE;
            font-size: 12px;
        }
        .text-right {
            text-align: right;
        }
        .comparativo {
            display: flex;
            justify-content: space-between;
        }
        .col-50 {
            width: 48%;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }
        .verificacion {
            margin-top: 20px;
            padding: 15px;
            border: 2px solid #4472C4;
            background-color: #F0F4F8;
        }
        .verificacion.error {
            border-color: #C00000;
            background-color: #FFE6E6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa_nombre ?? 'Empresa' }}</h2>
        <h2>BALANCE GENERAL (Estado de Situación Financiera)</h2>
        <h3>Conforme a las Normas de Información Financiera (NIF B-6)</h3>
        <h3>Al {{ $fecha_corte ?? date('d/m/Y') }}</h3>
        <h3>Cifras expresadas en pesos mexicanos</h3>
    </div>

    <div class="info-section">
        <div>RFC: {{ $rfc ?? '' }}</div>
        <div>Periodo: {{ $periodo ?? '' }}/{{ $ejercicio ?? '' }}</div>
        <div>Fecha de emisión: {{ $fecha_emision ?? date('d/m/Y') }}</div>
    </div>

    <!-- ACTIVO -->
    <table>
        <thead>
            <tr>
                <th style="width: 50%">ACTIVO</th>
                <th class="text-right" style="width: 25%">{{ $ejercicio ?? date('Y') }}</th>
                <th class="text-right" style="width: 25%">{{ ($ejercicio ?? date('Y')) - 1 }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- ACTIVO CIRCULANTE -->
            <tr class="nivel-1">
                <td>ACTIVO CIRCULANTE</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($activo_circulante ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel ?? 2 }}">
                <td>{{ $cuenta->nombre ?? '' }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_actual ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_anterior ?? 0, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-section">
                <td>TOTAL ACTIVO CIRCULANTE</td>
                <td class="text-right">{{ number_format($total_activo_circulante ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_activo_circulante_ant ?? 0, 2) }}</td>
            </tr>

            <!-- ACTIVO NO CIRCULANTE -->
            <tr class="nivel-1">
                <td>ACTIVO NO CIRCULANTE</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($activo_no_circulante ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_actual, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_anterior, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-section">
                <td>TOTAL ACTIVO NO CIRCULANTE</td>
                <td class="text-right">{{ number_format($total_activo_no_circulante ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_activo_no_circulante_ant ?? 0, 2) }}</td>
            </tr>

            <!-- TOTAL ACTIVO -->
            <tr class="total">
                <td>TOTAL ACTIVO</td>
                <td class="text-right">{{ number_format($total_activo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_activo_ant ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- PASIVO Y CAPITAL -->
    <table>
        <thead>
            <tr>
                <th style="width: 50%">PASIVO Y CAPITAL CONTABLE</th>
                <th class="text-right" style="width: 25%">{{ $ejercicio ?? date("Y") }}</th>
                <th class="text-right" style="width: 25%">{{ ($ejercicio ?? date("Y")) - 1 }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- PASIVO CIRCULANTE -->
            <tr class="nivel-1">
                <td>PASIVO CIRCULANTE</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($pasivo_circulante ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_actual, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_anterior, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-section">
                <td>TOTAL PASIVO CIRCULANTE</td>
                <td class="text-right">{{ number_format($total_pasivo_circulante ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_pasivo_circulante_ant ?? 0, 2) }}</td>
            </tr>

            <!-- PASIVO NO CIRCULANTE -->
            <tr class="nivel-1">
                <td>PASIVO NO CIRCULANTE</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($pasivo_no_circulante ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_actual, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_anterior, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-section">
                <td>TOTAL PASIVO NO CIRCULANTE</td>
                <td class="text-right">{{ number_format($total_pasivo_no_circulante ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_pasivo_no_circulante_ant ?? 0, 2) }}</td>
            </tr>

            <!-- TOTAL PASIVO -->
            <tr class="total">
                <td>TOTAL PASIVO</td>
                <td class="text-right">{{ number_format($total_pasivo ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_pasivo_ant ?? 0, 2) }}</td>
            </tr>

            <!-- CAPITAL CONTABLE -->
            <tr class="nivel-1">
                <td>CAPITAL CONTABLE</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            @foreach(($capital ?? []) as $cuenta)
            <tr class="nivel-{{ $cuenta->nivel }}">
                <td>{{ $cuenta->nombre }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_actual, 2) }}</td>
                <td class="text-right">{{ number_format($cuenta->saldo_anterior, 2) }}</td>
            </tr>
            @endforeach
            <tr class="nivel-2">
                <td>Resultado del Ejercicio</td>
                <td class="text-right">{{ number_format($resultado_ejercicio ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($resultado_ejercicio_ant ?? 0, 2) }}</td>
            </tr>
            <tr class="total-section">
                <td>TOTAL CAPITAL CONTABLE</td>
                <td class="text-right">{{ number_format($total_capital ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_capital_ant ?? 0, 2) }}</td>
            </tr>

            <!-- TOTAL PASIVO + CAPITAL -->
            <tr class="total">
                <td>TOTAL PASIVO Y CAPITAL CONTABLE</td>
                <td class="text-right">{{ number_format($total_pasivo_capital ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($total_pasivo_capital_ant ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- VERIFICACIÓN -->
    <div class="verificacion {{ ($balance_cuadrado ?? false) ? '' : 'error' }}">
        <strong>VERIFICACIÓN DE LA ECUACIÓN CONTABLE:</strong><br>
        ACTIVO = PASIVO + CAPITAL CONTABLE<br>
        {{ number_format($total_activo ?? 0, 2) }} = {{ number_format($total_pasivo_capital ?? 0, 2) }}<br>
        @if($balance_cuadrado ?? false)
            <span style="color: green;">✓ Balance cuadrado correctamente</span>
        @else
            <span style="color: red;">✗ DIFERENCIA: {{ number_format(abs(($total_activo ?? 0) - ($total_pasivo_capital ?? 0)), 2) }}</span>
        @endif
    </div>

    <div class="footer">
        <p>Este reporte ha sido elaborado conforme a las Normas de Información Financiera (NIF) vigentes en México.</p>
        <p>NIF B-6: Estado de Situación Financiera</p>
    </div>
</body>
</html>
