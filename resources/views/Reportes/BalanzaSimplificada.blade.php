<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balanza de Comprobación Simplificada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
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
            font-size: 9pt;
        }

        thead th {
            background-color: #2c3e50;
            color: white;
            padding: 10px 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #1a252f;
            font-size: 9pt;
        }

        tbody td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f0f0;
        }

        .codigo {
            text-align: center;
            width: 100px;
            font-family: 'Courier New', monospace;
        }

        .cuenta {
            text-align: left;
            padding-left: 10px;
        }

        .nivel-1 {
            font-weight: bold;
            background-color: #ecf0f1 !important;
        }

        .nivel-2 {
            padding-left: 20px;
        }

        .nivel-3 {
            padding-left: 35px;
            font-size: 8.5pt;
        }

        .nivel-4 {
            padding-left: 50px;
            font-size: 8.5pt;
        }

        .numero {
            text-align: right;
            padding-right: 10px;
            font-family: 'Courier New', monospace;
        }

        .totales {
            font-weight: bold;
            background-color: #34495e !important;
            color: white !important;
        }

        .totales td {
            padding: 10px 8px;
            border: 2px solid #2c3e50;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }

        @page {
            margin: 15mm;
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
        <h2>BALANZA DE COMPROBACIÓN SIMPLIFICADA</h2>
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
                <th class="codigo">CÓDIGO</th>
                <th class="cuenta">NOMBRE DE LA CUENTA</th>
                <th>SALDO INICIAL</th>
                <th>CARGOS</th>
                <th>ABONOS</th>
                <th>SALDO FINAL</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_inicial = 0;
                $total_cargos = 0;
                $total_abonos = 0;
                $total_final = 0;
            @endphp

            @foreach($cuentas ?? [] as $cuenta)
                @php
                    // Obtener naturaleza
                    $naturaleza = $cuenta->naturaleza ?? 'D';

                    // Saldos y movimientos
                    // Para cuentas acreedoras (A), invertir el signo de los saldos para que la balanza cuadre
                    $multiplicador = ($naturaleza == 'A') ? -1 : 1;

                    $saldo_inicial_raw = $cuenta->anterior ?? 0;
                    $saldo_final_raw = $cuenta->final ?? 0;

                    $saldo_inicial = $saldo_inicial_raw * $multiplicador;
                    $saldo_final = $saldo_final_raw * $multiplicador;

                    $cargos = $cuenta->cargos ?? 0;
                    $abonos = $cuenta->abonos ?? 0;

                    // Determinar clase por nivel
                    $nivel = $cuenta->nivel ?? 1;
                    $clase_nivel = 'nivel-' . $nivel;

                    // Acumular totales SOLO para cuentas de mayor (nivel 1)
                    if ($nivel == 1) {
                        $total_inicial += $saldo_inicial;
                        $total_cargos += $cargos;
                        $total_abonos += $abonos;
                        $total_final += $saldo_final;
                    }
                @endphp

                <tr class="{{ $clase_nivel }}">
                    <td class="codigo">{{ $cuenta->codigo ?? '' }}</td>
                    <td class="cuenta">{{ $cuenta->cuenta ?? $cuenta->nombre ?? '' }}</td>
                    <td class="numero">{{ $saldo_inicial != 0 ? number_format($saldo_inicial, 2) : '' }}</td>
                    <td class="numero">{{ $cargos > 0 ? number_format($cargos, 2) : '' }}</td>
                    <td class="numero">{{ $abonos > 0 ? number_format($abonos, 2) : '' }}</td>
                    <td class="numero">{{ $saldo_final != 0 ? number_format($saldo_final, 2) : '' }}</td>
                </tr>
            @endforeach

            <tr class="totales">
                <td colspan="2" style="text-align: center;">SUMAS TOTALES</td>
                <td class="numero">{{ number_format($total_inicial, 2) }}</td>
                <td class="numero">{{ number_format($total_cargos, 2) }}</td>
                <td class="numero">{{ number_format($total_abonos, 2) }}</td>
                <td class="numero">{{ number_format($total_final, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p style="font-style: italic;">
            Este documento se genera de conformidad con las disposiciones fiscales vigentes en México
        </p>
        <p style="margin-top: 10px;">
            <strong>Nota:</strong> Los saldos consideran naturalezas contables. Cuentas deudoras (Activo, Gastos) se muestran positivas; Cuentas acreedoras (Pasivo, Capital, Ingresos) se muestran negativas para que la balanza cuadre.
        </p>
    </div>
</body>
</html>
