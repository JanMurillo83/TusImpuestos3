<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Declaración Mensual de IVA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { font-size: 16pt; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .header h2 { font-size: 12pt; color: #e74c3c; margin-bottom: 8px; }
        .header-info { font-size: 9pt; display: flex; justify-content: space-between; margin-top: 8px; }
        .seccion { margin-bottom: 25px; page-break-inside: avoid; }
        .seccion h3 { background-color: #34495e; color: white; padding: 8px; font-size: 11pt; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        td { padding: 8px; border: 1px solid #ddd; }
        .label { width: 60%; font-weight: bold; background-color: #ecf0f1; }
        .value { width: 40%; text-align: right; font-family: 'Courier New', monospace; font-size: 11pt; }
        .subtotal { background-color: #bdc3c7; font-weight: bold; }
        .total { background-color: #34495e; color: white; font-weight: bold; font-size: 12pt; }
        .a-favor { background-color: #d5f4e6 !important; color: #27ae60; }
        .a-cargo { background-color: #fadbd8 !important; color: #e74c3c; }
        .resumen { background-color: #fff9e6; border: 2px solid #f39c12; padding: 15px; text-align: center; margin-top: 20px; }
        .resumen h3 { color: #856404; margin-bottom: 10px; }
        @page { margin: 15mm 10mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>📊 DECLARACIÓN MENSUAL DE IVA</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Periodo:</strong> {{ $periodo }}/{{ $ejercicio }}</div>
            <div><strong>Fecha de Emisión:</strong> {{ $fecha_emision }}</div>
        </div>
    </div>

    <div class="seccion">
        <h3>I. IVA CAUSADO (VENTAS)</h3>
        <table>
            <tr>
                <td class="label">Total de Ventas (Base Gravada)</td>
                <td class="value">$ {{ number_format($total_ventas, 2) }}</td>
            </tr>
            <tr>
                <td class="label">IVA Trasladado Cobrado (16%)</td>
                <td class="value">$ {{ number_format($iva_trasladado, 2) }}</td>
            </tr>
            <tr>
                <td class="label">(-) IVA Retenido por Clientes</td>
                <td class="value">$ {{ number_format($iva_retenido_cobrado, 2) }}</td>
            </tr>
            <tr class="subtotal">
                <td class="label">IVA Causado Neto</td>
                <td class="value">$ {{ number_format($iva_trasladado - $iva_retenido_cobrado, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-size: 8pt; color: #666; padding: 5px;">
                    Total de CFDIs Emitidos: {{ $total_cfdis_emitidos }}
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>II. IVA ACREDITABLE (COMPRAS)</h3>
        <table>
            <tr>
                <td class="label">Total de Compras (Base Gravada)</td>
                <td class="value">$ {{ number_format($total_compras, 2) }}</td>
            </tr>
            <tr>
                <td class="label">IVA Acreditable (16%)</td>
                <td class="value">$ {{ number_format($iva_acreditable, 2) }}</td>
            </tr>
            <tr>
                <td class="label">(+) IVA Retenido a Proveedores</td>
                <td class="value">$ {{ number_format($iva_retenido_pagado, 2) }}</td>
            </tr>
            <tr class="subtotal">
                <td class="label">IVA Acreditable Total</td>
                <td class="value">$ {{ number_format($iva_acreditable + $iva_retenido_pagado, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-size: 8pt; color: #666; padding: 5px;">
                    Total de CFDIs Recibidos: {{ $total_cfdis_recibidos }}
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>III. RESULTADO</h3>
        <table>
            <tr class="total {{ $iva_a_cargo > 0 ? 'a-cargo' : 'a-favor' }}">
                <td class="label">
                    @if($iva_a_cargo > 0)
                        IVA A CARGO (A PAGAR)
                    @elseif($iva_a_cargo < 0)
                        IVA A FAVOR
                    @else
                        SIN IVA A PAGAR NI A FAVOR
                    @endif
                </td>
                <td class="value">$ {{ number_format(abs($iva_a_cargo), 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="resumen">
        <h3>📋 RESUMEN PARA DECLARACIÓN</h3>
        <p style="margin-top: 10px; font-size: 9pt;">
            <strong>IVA Causado:</strong> ${{ number_format($iva_trasladado, 2) }} &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>IVA Acreditable:</strong> ${{ number_format($iva_acreditable, 2) }} &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong style="color: {{ $iva_a_cargo > 0 ? '#e74c3c' : '#27ae60' }};">
                {{ $iva_a_cargo > 0 ? 'A Pagar' : 'A Favor' }}: ${{ number_format(abs($iva_a_cargo), 2) }}
            </strong>
        </p>
    </div>

    <div style="margin-top: 30px; font-size: 8pt; color: #666; text-align: center;">
        <p>Este reporte es un auxiliar para la declaración mensual ante el SAT.</p>
        <p>Verifique los datos antes de presentar su declaración oficial.</p>
    </div>
</body>
</html>
