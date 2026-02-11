<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DIOT - Declaración Informativa de Operaciones con Terceros</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 7pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 3px solid #2c3e50; padding-bottom: 8px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin-bottom: 3px; }
        .header h2 { font-size: 10pt; color: #e74c3c; margin-bottom: 5px; }
        .header-info { font-size: 7pt; display: flex; justify-content: space-between; }
        .resumen { background-color: #fff9e6; border: 2px solid #f39c12; padding: 10px; margin-bottom: 15px; text-align: center; }
        .resumen-datos { display: flex; justify-content: space-around; font-size: 8pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 6.5pt; }
        thead th { background-color: #34495e; color: white; padding: 5px 3px; text-align: center; border: 1px solid #2c3e50; }
        tbody td { padding: 4px 3px; border: 1px solid #ddd; }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .rfc { width: 100px; font-family: 'Courier New', monospace; }
        .numero { text-align: right; font-family: 'Courier New', monospace; }
        .totales { background-color: #f39c12; font-weight: bold; }
        @page { margin: 12mm 8mm; size: landscape; }
        @media print { * { -webkit-print-color-adjust: exact !important; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>DIOT - DECLARACIÓN INFORMATIVA DE OPERACIONES CON TERCEROS</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Periodo:</strong> {{ $periodo }}/{{ $ejercicio }}</div>
            <div><strong>Fecha:</strong> {{ $fecha_emision }}</div>
        </div>
    </div>

    <div class="resumen">
        <div class="resumen-datos">
            <div>Total Proveedores: <strong>{{ $total_proveedores }}</strong></div>
            <div>Subtotal: <strong>${{ number_format($total_subtotal, 2) }}</strong></div>
            <div>IVA: <strong>${{ number_format($total_iva, 2) }}</strong></div>
            <div>Ret. IVA: <strong>${{ number_format($total_ret_iva, 2) }}</strong></div>
            <div>Ret. ISR: <strong>${{ number_format($total_ret_isr, 2) }}</strong></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="rfc">RFC</th>
                <th>NOMBRE / RAZÓN SOCIAL</th>
                <th class="numero">SUBTOTAL</th>
                <th class="numero">IVA TRASLADADO</th>
                <th class="numero">RET. IVA</th>
                <th class="numero">RET. ISR</th>
                <th class="numero">TOTAL</th>
                <th style="width: 40px;">FAC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proveedores as $prov)
            <tr>
                <td class="rfc">{{ $prov['rfc'] }}</td>
                <td>{{ $prov['nombre'] }}</td>
                <td class="numero">{{ number_format($prov['subtotal'], 2) }}</td>
                <td class="numero">{{ number_format($prov['iva_trasladado'], 2) }}</td>
                <td class="numero">{{ number_format($prov['iva_retenido'], 2) }}</td>
                <td class="numero">{{ number_format($prov['isr_retenido'], 2) }}</td>
                <td class="numero">{{ number_format($prov['total'], 2) }}</td>
                <td style="text-align: center;">{{ $prov['num_facturas'] }}</td>
            </tr>
            @endforeach
            <tr class="totales">
                <td colspan="2">TOTALES:</td>
                <td class="numero">{{ number_format($total_subtotal, 2) }}</td>
                <td class="numero">{{ number_format($total_iva, 2) }}</td>
                <td class="numero">{{ number_format($total_ret_iva, 2) }}</td>
                <td class="numero">{{ number_format($total_ret_isr, 2) }}</td>
                <td class="numero">{{ number_format(array_sum(array_column($proveedores, 'total')), 2) }}</td>
                <td style="text-align: center;">{{ array_sum(array_column($proveedores, 'num_facturas')) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 7pt; color: #666;">
        <p><strong>Nota:</strong> Este reporte debe presentarse mensualmente al SAT a través del portal.</p>
        <p>Incluye todas las operaciones con proveedores que generaron IVA acreditable en el periodo.</p>
    </div>
</body>
</html>
