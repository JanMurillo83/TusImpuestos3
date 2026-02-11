<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Razones Financieras</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; padding: 10mm; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin-bottom: 5px; }
        .header h2 { font-size: 11pt; color: #666; margin-bottom: 8px; }
        .header-info { font-size: 9pt; display: flex; justify-content: space-between; margin-top: 8px; }
        .seccion { margin-bottom: 25px; }
        .seccion h3 { background-color: #34495e; color: white; padding: 8px; font-size: 11pt; margin-bottom: 10px; }
        .tabla-razones { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .tabla-razones td { padding: 8px; border: 1px solid #ddd; }
        .tabla-razones .label { width: 60%; font-weight: bold; background-color: #ecf0f1; }
        .tabla-razones .value { width: 40%; text-align: right; font-family: 'Courier New', monospace; }
        .excelente { background-color: #d5f4e6 !important; color: #27ae60; }
        .bueno { background-color: #fff9e6 !important; color: #f39c12; }
        .malo { background-color: #fadbd8 !important; color: #e74c3c; }
        .descripcion { font-size: 8pt; color: #666; font-style: italic; margin-top: 5px; }
        @page { margin: 15mm 10mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa_nombre ?? 'Empresa' }}</h1>
        <h2>ANÁLISIS DE RAZONES FINANCIERAS</h2>
        <div class="header-info">
            <div><strong>RFC:</strong> {{ $rfc ?? 'N/A' }}</div>
            <div><strong>Periodo:</strong> {{ $periodo }}/{{ $ejercicio }}</div>
            <div><strong>Fecha:</strong> {{ $fecha_emision ?? date('d/m/Y') }}</div>
        </div>
    </div>

    <div class="seccion">
        <h3>1. RAZONES DE LIQUIDEZ</h3>
        <table class="tabla-razones">
            <tr>
                <td class="label">Razón Circulante (Activo Circulante / Pasivo Circulante)</td>
                <td class="value {{ $razones['liquidez']['razon_circulante'] >= 1.5 ? 'excelente' : ($razones['liquidez']['razon_circulante'] >= 1.0 ? 'bueno' : 'malo') }}">
                    {{ number_format($razones['liquidez']['razon_circulante'], 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Mide la capacidad de pagar obligaciones a corto plazo. Ideal: > 1.5
                </td>
            </tr>
            <tr>
                <td class="label">Capital de Trabajo</td>
                <td class="value">$ {{ number_format($razones['liquidez']['capital_trabajo'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Recursos disponibles después de pagar pasivos circulantes.
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>2. RAZONES DE ENDEUDAMIENTO</h3>
        <table class="tabla-razones">
            <tr>
                <td class="label">Razón de Deuda Total (Pasivo Total / Activo Total)</td>
                <td class="value {{ $razones['endeudamiento']['deuda_total'] <= 0.5 ? 'excelente' : ($razones['endeudamiento']['deuda_total'] <= 0.7 ? 'bueno' : 'malo') }}">
                    {{ number_format($razones['endeudamiento']['deuda_total'] * 100, 1) }}%
                </td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Porcentaje de activos financiados por deuda. Ideal: < 50%
                </td>
            </tr>
            <tr>
                <td class="label">Deuda a Capital (Pasivo Total / Capital Contable)</td>
                <td class="value {{ $razones['endeudamiento']['deuda_capital'] <= 1.0 ? 'excelente' : ($razones['endeudamiento']['deuda_capital'] <= 2.0 ? 'bueno' : 'malo') }}">
                    {{ number_format($razones['endeudamiento']['deuda_capital'], 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Relación entre deuda y capital propio. Ideal: < 1.0
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>3. RAZONES DE RENTABILIDAD</h3>
        <table class="tabla-razones">
            <tr>
                <td class="label">Margen de Utilidad Neta</td>
                <td class="value {{ $razones['rentabilidad']['margen_neto'] >= 10 ? 'excelente' : ($razones['rentabilidad']['margen_neto'] >= 5 ? 'bueno' : 'malo') }}">
                    {{ number_format($razones['rentabilidad']['margen_neto'], 2) }}%
                </td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Utilidad generada por cada peso de ventas. Ideal: > 10%
                </td>
            </tr>
            <tr>
                <td class="label">ROA - Rendimiento sobre Activos</td>
                <td class="value {{ $razones['rentabilidad']['roa'] >= 5 ? 'excelente' : ($razones['rentabilidad']['roa'] >= 2 ? 'bueno' : 'malo') }}">
                    {{ number_format($razones['rentabilidad']['roa'], 2) }}%
                </td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Eficiencia en el uso de activos. Ideal: > 5%
                </td>
            </tr>
            <tr>
                <td class="label">ROE - Rendimiento sobre Capital</td>
                <td class="value {{ $razones['rentabilidad']['roe'] >= 15 ? 'excelente' : ($razones['rentabilidad']['roe'] >= 10 ? 'bueno' : 'malo') }}">
                    {{ number_format($razones['rentabilidad']['roe'], 2) }}%
                </td>
            </tr>
            <tr>
                <td colspan="2" class="descripcion">
                    Rentabilidad para los accionistas. Ideal: > 15%
                </td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h3>VALORES BASE UTILIZADOS</h3>
        <table class="tabla-razones">
            <tr>
                <td class="label">Activo Circulante</td>
                <td class="value">$ {{ number_format($valores_base['activo_circulante'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Activo Total</td>
                <td class="value">$ {{ number_format($valores_base['activo_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Pasivo Circulante</td>
                <td class="value">$ {{ number_format($valores_base['pasivo_circulante'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Pasivo Total</td>
                <td class="value">$ {{ number_format($valores_base['pasivo_total'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Capital Contable</td>
                <td class="value">$ {{ number_format($valores_base['capital_contable'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Ventas Netas</td>
                <td class="value">$ {{ number_format($valores_base['ventas_netas'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Utilidad Neta</td>
                <td class="value">$ {{ number_format($valores_base['utilidad_neta'], 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
