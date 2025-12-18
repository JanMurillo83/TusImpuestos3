<div>
    <?php
    $data_cliente = \App\Models\EstadCXP_F::where('clave',$cliente)->first();
    $clave = $data_cliente->clave;
    $nombre = $data_cliente->cliente;
    $clie_cat = \App\Models\Proveedores::where('team_id',\Filament\Facades\Filament::getTenant()->id)
        ->where('cuenta_contable',$cliente)->first();
    $facturas = $data_cliente->facturas;
    $venta = array_column($facturas,'importe');
    $ventas = array_sum($venta);
    $pago = array_column($facturas,'pagos');
    $pagos = array_sum($pago);
    $saldo = $ventas - $pagos;
    $vencido = 0;
    $act = \Carbon\Carbon::create(\Filament\Facades\Filament::getTenant()->ejercicio,\Filament\Facades\Filament::getTenant()->periodo);
    //dd($act)
    foreach ($facturas as $factura)
    {
        $ven = \Carbon\Carbon::create($factura['vencimiento']);
        if($ven < $act && floatval($factura['saldo']) != 0)
        {
            $vencido+=$factura['saldo'];
        }

    }
    $porcentaje = $vencido * 100 / max($saldo,1);
    ?>
    <style>
        .icon {
            width: 20px;
            height: 20px;
            display: inline;
            margin-right: 10px;
            position: relative;
        }
        .icon2 {
            width: 40px;
            height: 40px;
            display: inline;
            margin-right: 10px;
            position: relative;
        }
        .iconlabel{
            display: block;
            font-size: 18px;
        }
        @media print{
            html, body {
                -webkit-print-color-adjust: exact;
            }
        }
        table {
            border-collapse: collapse; /* Collapses borders into a single border */
            width: 100%; /* Ensures the table takes up the full width available */
        }

        th, td {
            text-align: left; /* Aligns text to the left */
            padding: 8px; /* Adds space around content */
        }

        tr:nth-child(even) {
            background-color: #f2f2f2; /* Gray background for even rows */
        }

        /* Optional: Add a different background color for the table header */
        th {
            background-color: #04AA6D;
            color: white;
        }
    </style>

    <div class="container mx-auto mt-2 ml-2">
        <div class="row" style="margin-top: 2rem">
            <table>
                <thead>
                <tr>
                    <th colspan="6">Estado de Cuenta del proveedor</th>
                </tr>
                <tr>
                    <th>Proveedor:</th>
                    <th colspan="2">{{$clie_cat->nombre}}</th>
                    <th>RFC:</th>
                    <th colspan="2">{{$clie_cat->rfc}}</th>
                </tr>
                <tr>
                    <th>Contacto:</th>
                    <th>{{$clie_cat->contacto}}</th>
                    <th>Teléfono:</th>
                    <th>{{$clie_cat->telefono}}</th>
                    <th>Correo:</th>
                    <th>{{$clie_cat->correo}}</th>
                </tr>
                <tr>
                    <th>Importe de Ventas:</th>
                    <th>{{'$'.number_format($ventas,2)}}</th>
                    <th>Pagos Totales:</th>
                    <th>{{'$'.number_format($pagos,2)}}</th>
                    <th>Saldo:</th>
                    <th>{{'$'.number_format($saldo,2)}}</th>
                </tr>
                <tr>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Vencimiento</th>
                    <th>Importe</th>
                    <th>Pagos</th>
                    <th>Saldo</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $tot_importe = 0;
                $tot_pagos = 0;
                $tot_saldo = 0;
                ?>
                @foreach($facturas as $factura)
                        <?php
                        $tot_importe+= floatval($factura['importe']);
                        $tot_pagos+= floatval($factura['pagos']);
                        $tot_saldo+= floatval($factura['saldo']);
                        ?>
                    <tr>
                        <td>{{$factura['factura']}}</td>
                        <td>{{\Carbon\Carbon::create($factura['fecha'])->format('d-m-Y')}}</td>
                        <td>{{\Carbon\Carbon::create($factura['vencimiento'])->format('d-m-Y')}}</td>
                        <td style="text-align: right">{{'$'.number_format($factura['importe'],2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($factura['pagos'],2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($factura['saldo'],2)}}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3">Totales: </td>
                    <td style="text-align: right;font-weight: bold">{{'$'.number_format($tot_importe,2)}}</td>
                    <td style="text-align: right;font-weight: bold">{{'$'.number_format($tot_pagos,2)}}</td>
                    <td style="text-align: right;font-weight: bold">{{'$'.number_format($tot_saldo,2)}}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
