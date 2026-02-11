<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pólizas Descuadradas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin-bottom: 5px; }
        .header h2 { font-size: 11pt; color: #e74c3c; margin-bottom: 8px; }
        .header-info { font-size: 9pt; display: flex; justify-content: space-between; }
        .resumen { background-color: #fff3cd; border: 2px solid #f39c12; padding: 15px; margin-bottom: 20px; text-align: center; }
        .resumen h3 { color: #856404; margin-bottom: 10px; }
        .resumen-datos { display: flex; justify-content: space-around; }
        .resumen-item { font-size: 12pt; }
        .resumen-item strong { display: block; font-size: 18pt; color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8pt; }
        thead th { background-color: #e74c3c; color: white; padding: 8px 5px; text-align: center; border: 1px solid #c0392b; }
        tbody td { padding: 6px; border: 1px solid #ddd; }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .folio { width: 80px; text-align: center; font-family: 'Courier New', monospace; }
        .fecha { width: 90px; text-align: center; }
        .numero { text-align: right; padding-right: 8px; font-family: 'Courier New', monospace; width: 110px; }
        .diferencia-positiva { background-color: #fadbd8; color: #c0392b; }
        .diferencia-negativa { background-color: #d5f4e6; color: #27ae60; }
        .sin-errores { background-color: #d5f4e6; border: 2px solid #27ae60; padding: 30px; text-align: center; }
        .sin-errores h3 { color: #27ae60; font-size: 16pt; }
        @page { margin: 15mm 10mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>⚠️ REPORTE DE PÓLIZAS DESCUADRADAS</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Periodo:</strong> {{ $periodo }}/{{ $ejercicio }}</div>
            <div><strong>Fecha:</strong> {{ $fecha_emision ?? date('d/m/Y') }}</div>
        </div>
    </div>

    <div class="resumen">
        <h3>RESUMEN DE CONTROL</h3>
        <div class="resumen-datos">
            <div class="resumen-item">
                Total de Pólizas
                <strong>{{ $total_polizas }}</strong>
            </div>
            <div class="resumen-item">
                Pólizas Descuadradas
                <strong>{{ $total_descuadradas }}</strong>
            </div>
            <div class="resumen-item">
                Porcentaje de Error
                <strong>{{ $total_polizas > 0 ? number_format(($total_descuadradas / $total_polizas) * 100, 1) : 0 }}%</strong>
            </div>
        </div>
    </div>

    @if(count($polizas) == 0)
        <div class="sin-errores">
            <h3>✓ No se encontraron pólizas descuadradas</h3>
            <p style="margin-top: 10px;">Todas las pólizas del periodo están correctamente cuadradas.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th class="folio">FOLIO</th>
                    <th class="folio">TIPO</th>
                    <th class="fecha">FECHA</th>
                    <th>CONCEPTO</th>
                    <th class="numero">TOTAL CARGO</th>
                    <th class="numero">TOTAL ABONO</th>
                    <th class="numero">DIFERENCIA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($polizas as $poliza)
                <tr>
                    <td class="folio">{{ $poliza->folio }}</td>
                    <td class="folio">{{ $poliza->tipo }}</td>
                    <td class="fecha">{{ $poliza->fecha }}</td>
                    <td>{{ $poliza->concepto }}</td>
                    <td class="numero">{{ number_format($poliza->total_cargo, 2) }}</td>
                    <td class="numero">{{ number_format($poliza->total_abono, 2) }}</td>
                    <td class="numero {{ $poliza->diferencia > 0 ? 'diferencia-positiva' : 'diferencia-negativa' }}">
                        {{ number_format($poliza->diferencia, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
