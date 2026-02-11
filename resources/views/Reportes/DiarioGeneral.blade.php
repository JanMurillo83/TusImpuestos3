<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diario General</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin-bottom: 3px; }
        .header h2 { font-size: 10pt; color: #666; margin-bottom: 5px; }
        .header-info { font-size: 8pt; display: flex; justify-content: space-between; margin-top: 5px; }
        .poliza-header { background-color: #2c3e50; color: white; padding: 6px 8px; margin-top: 15px; margin-bottom: 5px; font-weight: bold; }
        .poliza-descuadrada { background-color: #e74c3c !important; }
        .poliza-info { font-size: 7pt; color: #666; padding: 3px 8px; background-color: #ecf0f1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 7pt; }
        thead th { background-color: #34495e; color: white; padding: 5px 4px; text-align: center; border: 1px solid #2c3e50; }
        tbody td { padding: 3px; border: 1px solid #ddd; }
        .codigo { width: 80px; font-family: 'Courier New', monospace; }
        .numero { text-align: right; padding-right: 6px; font-family: 'Courier New', monospace; }
        .totales { background-color: #f39c12; font-weight: bold; }
        @page { margin: 12mm 10mm; }
        @media print { * { -webkit-print-color-adjust: exact !important; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>DIARIO GENERAL</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Del:</strong> {{ $periodo_inicio }}/{{ $ejercicio_inicio }} <strong>al</strong> {{ $periodo_fin }}/{{ $ejercicio_fin }}</div>
            <div><strong>Fecha:</strong> {{ $fecha_emision ?? date('d/m/Y') }}</div>
        </div>
    </div>

    @foreach($polizas ?? [] as $poliza)
        <div class="poliza-header {{ $poliza->descuadrada ? 'poliza-descuadrada' : '' }}">
            PÓLIZA {{ $poliza->tipo }}-{{ $poliza->folio }} | Fecha: {{ $poliza->fecha }} | Periodo: {{ $poliza->periodo }}/{{ $poliza->ejercicio }}
            @if($poliza->descuadrada) ⚠️ DESCUADRADA @endif
        </div>
        <div class="poliza-info">
            <strong>Concepto:</strong> {{ $poliza->concepto }} | <strong>Referencia:</strong> {{ $poliza->referencia ?? 'N/A' }}
        </div>

        <table>
            <thead>
                <tr>
                    <th class="codigo">CUENTA</th>
                    <th>NOMBRE</th>
                    <th class="numero">CARGO</th>
                    <th class="numero">ABONO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($poliza->auxiliares as $aux)
                <tr>
                    <td class="codigo">{{ $aux->codigo }}</td>
                    <td>{{ $aux->cuenta }}</td>
                    <td class="numero">{{ $aux->cargo > 0 ? number_format($aux->cargo, 2) : '' }}</td>
                    <td class="numero">{{ $aux->abono > 0 ? number_format($aux->abono, 2) : '' }}</td>
                </tr>
                @endforeach
                <tr class="totales">
                    <td colspan="2">TOTALES:</td>
                    <td class="numero">{{ number_format($poliza->total_cargo, 2) }}</td>
                    <td class="numero">{{ number_format($poliza->total_abono, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>
