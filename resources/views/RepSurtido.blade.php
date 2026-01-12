<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Orden de Surtido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
        }
        .logo {
            width: 150px;
        }
        .company-info {
            text-align: right;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        .label {
            font-weight: bold;
            background-color: #f2f2f2;
            width: 150px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
        .signature-section {
            margin-top: 100px;
            width: 100%;
        }
        .signature-box {
            width: 200px;
            border-top: 1px solid #000;
            display: inline-block;
            margin: 0 50px;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    @if($dafis && $dafis->logo)
                        <img src="{{ public_path('storage/' . $dafis->logo) }}" class="logo" alt="Logo">
                    @else
                        <h2>{{ $dafis->nombre ?? 'Empresa' }}</h2>
                    @endif
                </td>
                <td class="company-info">
                    <strong>{{ $dafis->nombre ?? '' }}</strong><br>
                    RFC: {{ $dafis->rfc ?? '' }}<br>
                    {{ $dafis->direccion ?? '' }}<br>
                    Tel: {{ $dafis->telefono ?? '' }}<br>
                    Email: {{ $dafis->correo ?? '' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="title">Orden de Surtido</div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Fecha de Impresión:</td>
                <td>{{ date('d/m/Y H:i:s') }}</td>
                <td class="label">Estado:</td>
                <td>{{ $surtido->estado }}</td>
            </tr>
            <tr>
                <td class="label">Factura Referencia:</td>
                <td>{{ $factura->docto ?? $surtido->factura_id }}</td>
                <td class="label">Cliente:</td>
                <td>{{ $factura->nombre ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Clave</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $item = \App\Models\Inventario::find($surtido->item_id);
            @endphp
            <tr>
                <td>{{ $item->clave ?? $surtido->item_id }}</td>
                <td>{{ $surtido->descr }}</td>
                <td>{{ number_format($surtido->cant, 2) }}</td>
                <td>${{ number_format($surtido->precio_u, 2) }}</td>
                <td>${{ number_format($surtido->precio_total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            Entregó
        </div>
        <div class="signature-box">
            Recibió (Cliente)
        </div>
    </div>

    <div class="footer">
        Este documento es una representación interna de un movimiento de inventario.
    </div>
</body>
</html>
