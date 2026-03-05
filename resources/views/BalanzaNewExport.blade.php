<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Balanza de Comprobación</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px; }
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 5px 0; font-size: 12px; }
        .footer-totals { background-color: #eee; font-weight: bold; }
        .diferencia { margin-top: 10px; text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa->name ?? 'Empresa' }}</h2>
        <p>Balanza de Comprobación</p>
        <p>Ejercicio: {{ $ejercicio }} | Periodo: {{ str_pad($periodo, 2, '0', STR_PAD_LEFT) }}</p>
        <p style="font-size: 10px; text-align: right;">Fecha de Emisión: {{ $fecha_emision ?? date('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Cuenta</th>
                <th>Saldo Inicial</th>
                <th>Cargos</th>
                <th>Abonos</th>
                <th>Saldo Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($balanza as $cuenta)
                <tr style="{{ $cuenta['nivel'] == 1 ? 'font-weight: bold; background-color: #f9f9f9;' : '' }}">
                    <td>
                        <span style="padding-left: {{ ($cuenta['nivel'] - 1) * 10 }}px;">
                            {{ $cuenta['codigo'] }}
                        </span>
                    </td>
                    <td>
                        <span style="padding-left: {{ ($cuenta['nivel'] - 1) * 10 }}px;">
                            {{ $cuenta['cuenta'] }}
                        </span>
                    </td>
                    <td class="text-right">{{ $cuenta['saldo_anterior'] != 0 ? number_format($cuenta['saldo_anterior'], 2) : '-' }}</td>
                    <td class="text-right">{{ $cuenta['cargos'] != 0 ? number_format($cuenta['cargos'], 2) : '-' }}</td>
                    <td class="text-right">{{ $cuenta['abonos'] != 0 ? number_format($cuenta['abonos'], 2) : '-' }}</td>
                    <td class="text-right">{{ $cuenta['saldo_final'] != 0 ? number_format($cuenta['saldo_final'], 2) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="footer-totals">
                <td colspan="2" class="text-right">TOTALES:</td>
                <td class="text-right">
                    @php
                        $saldo_inicial_neto = $totales['saldo_ant_deudor'] - $totales['saldo_ant_acreedor'];
                    @endphp
                    {{ number_format($saldo_inicial_neto, 2) }}
                </td>
                <td class="text-right">{{ number_format($totales['cargos'], 2) }}</td>
                <td class="text-right">{{ number_format($totales['abonos'], 2) }}</td>
                <td class="text-right">
                    @php
                        $saldo_final_neto = $totales['saldo_deudor'] - $totales['saldo_acreedor'];
                    @endphp
                    {{ number_format($saldo_final_neto, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    @php
        $diferencia = $totales['saldo_deudor'] - $totales['saldo_acreedor'];
    @endphp
    <div class="diferencia">
        @if(abs($diferencia) > 0.01)
            <span style="color: red;">Diferencia: ${{ number_format(abs($diferencia), 2) }}</span>
        @else
            <span style="color: green;">Balanza Cuadrada</span>
        @endif
    </div>
</body>
</html>
