<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DIOT</title>
</head>
<body>
    <?php
        $dafis = DB::table('datos_fiscales')->where('team_id',$empresa)->first();
    ?>
    <table>
        <thead>
            <tr>
                <th colspan="14" style="text-align: center; font-weight: bold; font-size: 14px;">
                    {{ $dafis->nombre }}
                </th>
            </tr>
            <tr>
                <th colspan="14" style="text-align: center; font-weight: bold; font-size: 14px;">
                    DECLARACIÓN INFORMATIVA DE OPERACIONES CON TERCEROS (DIOT)
                </th>
            </tr>
            <tr>
                <th colspan="14" style="text-align: center; font-size: 12px;">
                    Periodo: {{ str_pad($periodo, 2, '0', STR_PAD_LEFT) }}/{{ $ejercicio }}
                </th>
            </tr>
            <tr></tr>
            <tr style="background-color: #4472C4; color: white; font-weight: bold;">
                <th>RFC</th>
                <th>Nombre o Razón Social</th>
                <th>Tipo Tercero</th>
                <th>Tipo Operación</th>
                <th>Base IVA 16%</th>
                <th>IVA Pagado 16%</th>
                <th>Base IVA 8%</th>
                <th>IVA Pagado 8%</th>
                <th>Base IVA 0%</th>
                <th>Base Exenta</th>
                <th>IVA Retenido</th>
                <th>ISR Retenido</th>
                <th>Total Pagado</th>
                <th>País</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_base_16 = 0;
                $total_iva_16 = 0;
                $total_base_8 = 0;
                $total_iva_8 = 0;
                $total_base_0 = 0;
                $total_exenta = 0;
                $total_ret_iva = 0;
                $total_ret_isr = 0;
                $total_pagado = 0;
            @endphp
            @foreach($datos as $proveedor)
                <tr>
                    <td>{{ $proveedor['rfc'] }}</td>
                    <td>{{ $proveedor['nombre'] }}</td>
                    <td style="text-align: center;">{{ $proveedor['tipo_tercero'] }}</td>
                    <td style="text-align: center;">{{ $proveedor['tipo_operacion'] }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['base_iva_16'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['iva_16'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['base_iva_8'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['iva_8'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['base_iva_0'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['base_exenta'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['iva_retenido'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['isr_retenido'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($proveedor['total_pagado'], 2) }}</td>
                    <td style="text-align: center;">{{ $proveedor['pais'] }}</td>
                </tr>
                @php
                    $total_base_16 += $proveedor['base_iva_16'];
                    $total_iva_16 += $proveedor['iva_16'];
                    $total_base_8 += $proveedor['base_iva_8'];
                    $total_iva_8 += $proveedor['iva_8'];
                    $total_base_0 += $proveedor['base_iva_0'];
                    $total_exenta += $proveedor['base_exenta'];
                    $total_ret_iva += $proveedor['iva_retenido'];
                    $total_ret_isr += $proveedor['isr_retenido'];
                    $total_pagado += $proveedor['total_pagado'];
                @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #D9E1F2; font-weight: bold;">
                <td colspan="4" style="text-align: right;">TOTALES:</td>
                <td style="text-align: right;">{{ number_format($total_base_16, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_iva_16, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_base_8, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_iva_8, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_base_0, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_exenta, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_ret_iva, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_ret_isr, 2) }}</td>
                <td style="text-align: right;">{{ number_format($total_pagado, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
