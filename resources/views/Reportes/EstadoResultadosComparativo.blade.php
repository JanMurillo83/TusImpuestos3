<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Resultados Comparativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin-bottom: 3px; }
        .header h2 { font-size: 10pt; color: #666; margin-bottom: 5px; }
        .header-info { font-size: 8pt; display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 7pt; }
        thead th { background-color: #34495e; color: white; padding: 6px 4px; text-align: center; border: 1px solid #2c3e50; }
        tbody td { padding: 4px; border: 1px solid #ddd; }
        .codigo { width: 80px; font-family: 'Courier New', monospace; }
        .nombre { text-align: left; padding-left: 6px; }
        .numero { text-align: right; padding-right: 6px; font-family: 'Courier New', monospace; width: 100px; }
        .positivo { color: #27ae60; }
        .negativo { color: #e74c3c; }
        @page { margin: 12mm 8mm; size: landscape; }
        @media print { * { -webkit-print-color-adjust: exact !important; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>ESTADO DE RESULTADOS COMPARATIVO</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Periodo 1:</strong> {{ $periodo1 }}/{{ $ejercicio1 }} | <strong>Periodo 2:</strong> {{ $periodo2 }}/{{ $ejercicio2 }}</div>
            <div><strong>Fecha:</strong> {{ $fecha_emision ?? date('d/m/Y') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="codigo">CÓDIGO</th>
                <th class="nombre">CUENTA</th>
                <th class="numero">{{ $periodo1 }}/{{ $ejercicio1 }}</th>
                <th class="numero">{{ $periodo2 }}/{{ $ejercicio2 }}</th>
                <th class="numero">VARIACIÓN $</th>
                <th class="numero">VARIACIÓN %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuentas ?? [] as $cuenta)
                <tr>
                    <td class="codigo">{{ $cuenta->codigo }}</td>
                    <td class="nombre">{{ $cuenta->nombre }}</td>
                    <td class="numero">{{ number_format($cuenta->saldo1, 2) }}</td>
                    <td class="numero">{{ number_format($cuenta->saldo2, 2) }}</td>
                    <td class="numero {{ $cuenta->variacion >= 0 ? 'positivo' : 'negativo' }}">
                        {{ number_format($cuenta->variacion, 2) }}
                    </td>
                    <td class="numero {{ $cuenta->variacion >= 0 ? 'positivo' : 'negativo' }}">
                        {{ number_format($cuenta->variacion_porcentaje, 1) }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
