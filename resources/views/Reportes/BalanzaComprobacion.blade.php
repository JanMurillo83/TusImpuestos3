<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balanza de Comprobación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            padding: 15mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 12pt;
            font-weight: normal;
            color: #666;
            margin-bottom: 8px;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 9pt;
        }

        .header-info div {
            flex: 1;
        }

        .header-info .center {
            text-align: center;
        }

        .header-info .right {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 8pt;
        }

        thead th {
            background-color: #2c3e50;
            color: white;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #1a252f;
            font-size: 8pt;
        }

        tbody td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 8pt;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f0f0;
        }

        .codigo {
            text-align: center;
            width: 80px;
            font-family: 'Courier New', monospace;
        }

        .cuenta {
            text-align: left;
            padding-left: 8px;
        }

        .nivel-1 {
            font-weight: bold;
            background-color: #ecf0f1 !important;
        }

        .nivel-2 {
            padding-left: 15px;
        }

        .nivel-3 {
            padding-left: 25px;
            font-size: 7.5pt;
        }

        .nivel-4 {
            padding-left: 35px;
            font-size: 7.5pt;
        }

        .numero {
            text-align: right;
            padding-right: 8px;
            font-family: 'Courier New', monospace;
        }

        .totales {
            font-weight: bold;
            background-color: #34495e !important;
            color: white !important;
        }

        .totales td {
            padding: 8px 5px;
            border: 2px solid #2c3e50;
        }

        .diferencia {
            background-color: #e74c3c !important;
            color: white !important;
            font-weight: bold;
        }

        .cuadrado {
            background-color: #27ae60 !important;
            color: white !important;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #666;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .validacion {
            margin-top: 15px;
            padding: 10px;
            border: 2px solid #27ae60;
            background-color: #d4edda;
            text-align: center;
            font-weight: bold;
            color: #155724;
        }

        .validacion.error {
            border-color: #e74c3c;
            background-color: #f8d7da;
            color: #721c24;
        }

        @page {
            margin: 15mm;
            size: landscape;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>BALANZA DE COMPROBACIÓN</h2>
        <div class="header-info">
            <div class="left">
                <strong>RFC:</strong> {{ $rfc ?? 'N/A' }}
            </div>
            <div class="center">
                <strong>Periodo:</strong> {{ str_pad($periodo ?? 1, 2, '0', STR_PAD_LEFT) }}/{{ $ejercicio ?? date('Y') }}
                <br>
                <strong>Del:</strong> {{ $fecha_inicio ?? '01/01/'.date('Y') }} <strong>al:</strong> {{ $fecha_fin ?? date('d/m/Y') }}
            </div>
            <div class="right">
                <strong>Fecha de emisión:</strong> {{ $fecha_emision ?? date('d/m/Y') }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="codigo">CÓDIGO</th>
                <th rowspan="2" class="cuenta">NOMBRE DE LA CUENTA</th>
                <th colspan="2">SALDO INICIAL</th>
                <th colspan="2">MOVIMIENTOS</th>
                <th colspan="2">SALDO FINAL</th>
            </tr>
            <tr>
                <th>DEUDOR</th>
                <th>ACREEDOR</th>
                <th>DEBE</th>
                <th>HABER</th>
                <th>DEUDOR</th>
                <th>ACREEDOR</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_inicial_deudor = 0;
                $total_inicial_acreedor = 0;
                $total_debe = 0;
                $total_haber = 0;
                $total_final_deudor = 0;
                $total_final_acreedor = 0;
            @endphp

            @foreach($cuentas ?? [] as $cuenta)
                @php
                    // Obtener naturaleza de la cuenta
                    $naturaleza = $cuenta->naturaleza ?? 'D';

                    // Saldo inicial y final según naturaleza
                    $saldo_inicial = $cuenta->anterior ?? 0;
                    $saldo_final = $cuenta->final ?? 0;

                    // Movimientos
                    $debe = $cuenta->cargos ?? 0;
                    $haber = $cuenta->abonos ?? 0;

                    // Determinar columnas según naturaleza y signo del saldo
                    // Para cuentas DEUDORAS (D): saldo positivo = deudor, negativo = acreedor
                    // Para cuentas ACREEDORAS (A): saldo positivo = acreedor, negativo = deudor
                    if ($naturaleza == 'D') {
                        $inicial_deudor = $saldo_inicial >= 0 ? $saldo_inicial : 0;
                        $inicial_acreedor = $saldo_inicial < 0 ? abs($saldo_inicial) : 0;
                        $final_deudor = $saldo_final >= 0 ? $saldo_final : 0;
                        $final_acreedor = $saldo_final < 0 ? abs($saldo_final) : 0;
                    } else {
                        // Para acreedoras, invertir: saldo positivo va a acreedor
                        $inicial_deudor = $saldo_inicial < 0 ? abs($saldo_inicial) : 0;
                        $inicial_acreedor = $saldo_inicial >= 0 ? $saldo_inicial : 0;
                        $final_deudor = $saldo_final < 0 ? abs($saldo_final) : 0;
                        $final_acreedor = $saldo_final >= 0 ? $saldo_final : 0;
                    }

                    // Determinar clase por nivel
                    $nivel = $cuenta->nivel ?? 1;
                    $clase_nivel = 'nivel-' . $nivel;

                    // Acumular totales SOLO para cuentas de mayor (nivel 1)
                    if ($nivel == 1) {
                        $total_inicial_deudor += $inicial_deudor;
                        $total_inicial_acreedor += $inicial_acreedor;
                        $total_debe += $debe;
                        $total_haber += $haber;
                        $total_final_deudor += $final_deudor;
                        $total_final_acreedor += $final_acreedor;
                    }
                @endphp

                <tr class="{{ $clase_nivel }}">
                    <td class="codigo">{{ $cuenta->codigo ?? '' }}</td>
                    <td class="cuenta">{{ $cuenta->cuenta ?? $cuenta->nombre ?? '' }}</td>
                    <td class="numero">{{ $inicial_deudor > 0 ? number_format($inicial_deudor, 2) : '' }}</td>
                    <td class="numero">{{ $inicial_acreedor > 0 ? number_format($inicial_acreedor, 2) : '' }}</td>
                    <td class="numero">{{ $debe > 0 ? number_format($debe, 2) : '' }}</td>
                    <td class="numero">{{ $haber > 0 ? number_format($haber, 2) : '' }}</td>
                    <td class="numero">{{ $final_deudor > 0 ? number_format($final_deudor, 2) : '' }}</td>
                    <td class="numero">{{ $final_acreedor > 0 ? number_format($final_acreedor, 2) : '' }}</td>
                </tr>
            @endforeach

            <tr class="totales">
                <td colspan="2" style="text-align: center;">SUMAS TOTALES</td>
                <td class="numero">{{ number_format($total_inicial_deudor, 2) }}</td>
                <td class="numero">{{ number_format($total_inicial_acreedor, 2) }}</td>
                <td class="numero">{{ number_format($total_debe, 2) }}</td>
                <td class="numero">{{ number_format($total_haber, 2) }}</td>
                <td class="numero">{{ number_format($total_final_deudor, 2) }}</td>
                <td class="numero">{{ number_format($total_final_acreedor, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @php
        $diferencia_inicial = abs($total_inicial_deudor - $total_inicial_acreedor);
        $diferencia_movimientos = abs($total_debe - $total_haber);
        $diferencia_final = abs($total_final_deudor - $total_final_acreedor);

        $balanza_cuadrada = ($diferencia_movimientos < 0.01);
    @endphp

    <div class="validacion {{ $balanza_cuadrada ? '' : 'error' }}">
        @if($balanza_cuadrada)
            ✓ BALANZA CUADRADA - Los movimientos están balanceados correctamente
        @else
            ⚠ ADVERTENCIA: Diferencia en movimientos de ${{ number_format($diferencia_movimientos, 2) }}
        @endif
    </div>

    <div class="footer">
        <div class="footer-info">
            <div>
                <strong>Diferencia Saldo Inicial:</strong> ${{ number_format($diferencia_inicial, 2) }}
            </div>
            <div style="text-align: center;">
                <strong>Diferencia Movimientos:</strong> ${{ number_format($diferencia_movimientos, 2) }}
            </div>
            <div style="text-align: right;">
                <strong>Diferencia Saldo Final:</strong> ${{ number_format($diferencia_final, 2) }}
            </div>
        </div>
        <p style="margin-top: 15px; text-align: center; font-style: italic;">
            Este documento se genera de conformidad con las disposiciones fiscales vigentes en México
        </p>
    </div>
</body>
</html>
