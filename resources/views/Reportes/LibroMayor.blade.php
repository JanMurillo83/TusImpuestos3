<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libro Mayor</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
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

        .codigo {
            text-align: left;
            width: 100px;
            font-family: 'Courier New', monospace;
            padding-left: 8px;
        }

        .nombre {
            text-align: left;
            padding-left: 8px;
        }

        .numero {
            text-align: right;
            padding-right: 8px;
            font-family: 'Courier New', monospace;
            width: 120px;
        }

        .naturaleza {
            text-align: center;
            width: 60px;
        }

        .positivo {
            color: #27ae60;
        }

        .negativo {
            color: #e74c3c;
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
        <h2>LIBRO MAYOR</h2>
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

    <table>
        <thead>
            <tr>
                <th class="codigo">CÓDIGO</th>
                <th class="nombre">CUENTA</th>
                <th class="naturaleza">NAT.</th>
                <th class="numero">SALDO INICIAL</th>
                <th class="numero">CARGOS</th>
                <th class="numero">ABONOS</th>
                <th class="numero">SALDO FINAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuentas ?? [] as $cuenta)
                <tr>
                    <td class="codigo">{{ $cuenta->codigo }}</td>
                    <td class="nombre">{{ $cuenta->nombre }}</td>
                    <td class="naturaleza">{{ $cuenta->naturaleza ?? 'D' }}</td>
                    <td class="numero {{ $cuenta->saldo_inicial >= 0 ? 'positivo' : 'negativo' }}">
                        {{ number_format($cuenta->saldo_inicial ?? 0, 2) }}
                    </td>
                    <td class="numero">
                        {{ number_format($cuenta->total_cargos ?? 0, 2) }}
                    </td>
                    <td class="numero">
                        {{ number_format($cuenta->total_abonos ?? 0, 2) }}
                    </td>
                    <td class="numero {{ $cuenta->saldo_final >= 0 ? 'positivo' : 'negativo' }}">
                        {{ number_format($cuenta->saldo_final ?? 0, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
