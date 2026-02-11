<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Auxiliares</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            padding: 10mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 11pt;
            font-weight: normal;
            color: #666;
            margin-bottom: 8px;
        }

        .header-info {
            font-size: 9pt;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
        }

        .cuenta-header {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            margin: 20px 0 10px 0;
            font-weight: bold;
            font-size: 10pt;
        }

        .saldo-inicial {
            background-color: #ecf0f1;
            padding: 8px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }

        thead th {
            background-color: #34495e;
            color: white;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2c3e50;
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

        .fecha {
            text-align: center;
            width: 80px;
        }

        .folio {
            text-align: center;
            width: 70px;
            font-family: 'Courier New', monospace;
        }

        .concepto {
            text-align: left;
            padding-left: 8px;
        }

        .numero {
            text-align: right;
            padding-right: 8px;
            font-family: 'Courier New', monospace;
        }

        .saldo-final {
            background-color: #27ae60;
            color: white;
            font-weight: bold;
            padding: 10px;
            margin-top: 5px;
            text-align: right;
        }

        .saldo-deudor {
            background-color: #27ae60;
        }

        .saldo-acreedor {
            background-color: #e74c3c;
        }

        .totales-cuenta {
            background-color: #95a5a6;
            color: white;
            font-weight: bold;
            padding: 8px;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .totales-cuenta td {
            padding: 8px;
            border: 1px solid #7f8c8d;
            font-weight: bold;
        }

        .totales-globales {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            margin-top: 30px;
            font-weight: bold;
            font-size: 11pt;
            text-align: center;
            page-break-inside: avoid;
        }

        .totales-globales table {
            width: 100%;
            margin-top: 10px;
        }

        .totales-globales td {
            padding: 10px;
            font-size: 10pt;
            border: 1px solid #34495e;
        }

        .cuenta-section {
            page-break-inside: avoid;
            margin-bottom: 20px;
        }

        .page-break {
            page-break-after: always;
            page-break-inside: avoid;
            display: block;
            height: 1px;
            margin: 0;
            padding: 0;
            border: none;
            clear: both;
        }

        @page {
            margin: 15mm 10mm;
            size: portrait;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .page-break {
                display: block;
                page-break-after: always;
                page-break-inside: avoid;
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
        <h2>REPORTE DE AUXILIARES</h2>
        <div class="header-info">
            <div>
                <strong>RFC:</strong> {{ $rfc ?? 'N/A' }}
            </div>
            <div>
                <strong>Periodo:</strong> {{ $periodo_inicio ?? 1 }}/{{ $ejercicio_inicio ?? date('Y') }}
                al {{ $periodo_fin ?? 12 }}/{{ $ejercicio_fin ?? date('Y') }}
            </div>
            <div>
                <strong>Fecha:</strong> {{ $fecha_emision ?? date('d/m/Y') }}
            </div>
        </div>
    </div>

    @php
        $total_global_cargo = 0;
        $total_global_abono = 0;
    @endphp

    @foreach($cuentas ?? [] as $cuenta)
        <div class="cuenta-section">
            <div class="cuenta-header">
                {{ $cuenta->codigo }} - {{ $cuenta->nombre }}
            </div>

            <div class="saldo-inicial">
                Saldo Inicial: ${{ number_format($cuenta->saldo_inicial ?? 0, 2) }}
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="fecha">FECHA</th>
                        <th class="folio">FOLIO</th>
                        <th class="concepto">CONCEPTO</th>
                        <th class="numero">CARGO</th>
                        <th class="numero">ABONO</th>
                        <th class="numero">SALDO</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $saldo_acumulado = $cuenta->saldo_inicial ?? 0;
                        $total_cuenta_cargo = 0;
                        $total_cuenta_abono = 0;
                    @endphp

                    @foreach($cuenta->movimientos ?? [] as $movimiento)
                        @php
                            $cargo = $movimiento->cargo ?? 0;
                            $abono = $movimiento->abono ?? 0;

                            $total_cuenta_cargo += $cargo;
                            $total_cuenta_abono += $abono;

                            // Apply naturaleza when accumulating saldo
                            if (($cuenta->naturaleza ?? 'D') == 'A') {
                                $saldo_acumulado += ($abono - $cargo); // Acreedora
                            } else {
                                $saldo_acumulado += ($cargo - $abono); // Deudora
                            }
                        @endphp
                        <tr>
                            <td class="fecha">{{ $movimiento->fecha ?? '' }}</td>
                            <td class="folio">{{ $movimiento->tipo ?? '' }}-{{ $movimiento->folio ?? '' }}</td>
                            <td class="concepto">{{ $movimiento->concepto ?? '' }}</td>
                            <td class="numero">{{ $cargo > 0 ? number_format($cargo, 2) : '' }}</td>
                            <td class="numero">{{ $abono > 0 ? number_format($abono, 2) : '' }}</td>
                            <td class="numero">{{ number_format($saldo_acumulado, 2) }}</td>
                        </tr>
                    @endforeach

                    @php
                        $total_global_cargo += $total_cuenta_cargo;
                        $total_global_abono += $total_cuenta_abono;
                    @endphp

                    <!-- Totales por cuenta -->
                    <tr class="totales-cuenta">
                        <td colspan="3" style="text-align: right;">TOTAL CUENTA {{ $cuenta->codigo }}:</td>
                        <td class="numero">${{ number_format($total_cuenta_cargo, 2) }}</td>
                        <td class="numero">${{ number_format($total_cuenta_abono, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="saldo-final {{ $saldo_acumulado >= 0 ? 'saldo-deudor' : 'saldo-acreedor' }}">
                Saldo Final: ${{ number_format($saldo_acumulado, 2) }}
            </div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <!-- Totales globales al final del reporte -->
    <div class="totales-globales">
        <div>RESUMEN GENERAL DEL REPORTE</div>
        <table>
            <tr>
                <td style="text-align: center; width: 50%;">
                    <strong>TOTAL CARGOS:</strong><br>
                    ${{ number_format($total_global_cargo, 2) }}
                </td>
                <td style="text-align: center; width: 50%;">
                    <strong>TOTAL ABONOS:</strong><br>
                    ${{ number_format($total_global_abono, 2) }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
