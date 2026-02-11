<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Retenciones</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 3px solid #2c3e50; padding-bottom: 8px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin-bottom: 3px; }
        .header h2 { font-size: 10pt; color: #34495e; margin-bottom: 5px; }
        .header-info { font-size: 7pt; display: flex; justify-content: space-between; }
        .seccion { margin-bottom: 20px; page-break-inside: avoid; }
        .seccion h3 { background-color: #34495e; color: white; padding: 6px; font-size: 9pt; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 6.5pt; }
        thead th { background-color: #5d6d7e; color: white; padding: 5px 3px; text-align: center; border: 1px solid #34495e; }
        tbody td { padding: 4px 3px; border: 1px solid #ddd; }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .fecha { width: 70px; text-align: center; }
        .folio { width: 80px; font-family: 'Courier New', monospace; }
        .rfc { width: 100px; font-family: 'Courier New', monospace; }
        .tipo { width: 40px; text-align: center; font-weight: bold; }
        .numero { text-align: right; font-family: 'Courier New', monospace; }
        .totales { background-color: #f39c12; font-weight: bold; }
        .sin-datos { background-color: #d5f4e6; padding: 15px; text-align: center; color: #27ae60; }
        @page { margin: 12mm 8mm; size: landscape; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>REPORTE DE RETENCIONES DE ISR E IVA</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Periodo:</strong> {{ $periodo }}/{{ $ejercicio }}</div>
            <div><strong>Fecha:</strong> {{ $fecha_emision }}</div>
        </div>
    </div>

    <div class="seccion">
        <h3>I. RETENCIONES QUE NOS HICIERON (Facturas Emitidas)</h3>
        @if(count($ret_que_nos_hicieron) > 0)
        <table>
            <thead>
                <tr>
                    <th class="fecha">FECHA</th>
                    <th class="folio">FOLIO</th>
                    <th class="rfc">RFC CLIENTE</th>
                    <th>CLIENTE</th>
                    <th class="tipo">TIPO</th>
                    <th class="numero">BASE</th>
                    <th class="numero">RETENCIÓN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ret_que_nos_hicieron as $ret)
                <tr>
                    <td class="fecha">{{ $ret['fecha'] }}</td>
                    <td class="folio">{{ $ret['folio'] }}</td>
                    <td class="rfc">{{ $ret['rfc_cliente'] }}</td>
                    <td>{{ $ret['cliente'] }}</td>
                    <td class="tipo">{{ $ret['tipo'] }}</td>
                    <td class="numero">{{ number_format($ret['base'], 2) }}</td>
                    <td class="numero">{{ number_format($ret['importe'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="totales">
                    <td colspan="6">TOTAL RETENCIONES QUE NOS HICIERON:</td>
                    <td class="numero">$ {{ number_format($total_ret_nos_hicieron, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <div class="sin-datos">
            ✓ No hay retenciones que nos hayan hecho en este periodo
        </div>
        @endif
    </div>

    <div class="seccion">
        <h3>II. RETENCIONES QUE HICIMOS (Facturas Recibidas)</h3>
        @if(count($ret_que_hicimos) > 0)
        <table>
            <thead>
                <tr>
                    <th class="fecha">FECHA</th>
                    <th class="folio">FOLIO</th>
                    <th class="rfc">RFC PROVEEDOR</th>
                    <th>PROVEEDOR</th>
                    <th class="tipo">TIPO</th>
                    <th class="numero">BASE</th>
                    <th class="numero">RETENCIÓN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ret_que_hicimos as $ret)
                <tr>
                    <td class="fecha">{{ $ret['fecha'] }}</td>
                    <td class="folio">{{ $ret['folio'] }}</td>
                    <td class="rfc">{{ $ret['rfc_proveedor'] }}</td>
                    <td>{{ $ret['proveedor'] }}</td>
                    <td class="tipo">{{ $ret['tipo'] }}</td>
                    <td class="numero">{{ number_format($ret['base'], 2) }}</td>
                    <td class="numero">{{ number_format($ret['importe'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="totales">
                    <td colspan="6">TOTAL RETENCIONES QUE HICIMOS:</td>
                    <td class="numero">$ {{ number_format($total_ret_hicimos, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <div class="sin-datos">
            ✓ No hicimos retenciones en este periodo
        </div>
        @endif
    </div>

    <div style="margin-top: 15px; font-size: 7pt; color: #666;">
        <p><strong>Nota:</strong> Las retenciones que nos hicieron reducen el ingreso gravable.</p>
        <p>Las retenciones que hicimos deben enterarse al SAT mediante declaración complementaria.</p>
    </div>
</body>
</html>
