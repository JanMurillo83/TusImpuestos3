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
        <div class="grid gap-6">
            <div class="row-span-1 mb-2">
                <div class="col-md-6">
                    <label style="font-weight: bold; font-size: 20px; color: #9f1239">Estado de Cuenta del proveedor</label>
                    <div>
                        <label style="color: #aab2b3">Tus Impuestos - Cuentas por Pagar</label>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row-span-1 mt-2 mb-2" style="border: #7f8c8d solid 1px;border-radius: 20px; padding: 1rem; background-color: #f0f3f7">
                <div class="col-6">
                    <label class="iconlabel">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="blue">
                            <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
                        </svg>
                        Proveedor:
                    </label>
                    <label style="font-size: 15px; font-weight: bold">{{$clie_cat->nombre}}</label><br>
                    <label style="color: #aab2b3; margin-top: 1rem">{{$clie_cat->rfc}} - Cuenta: {{$cliente}}</label><br>
                    <label class="iconlabel">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="blue">
                            <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                            <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                        </svg>
                        Contacto:
                    </label>
                    <label style="font-size: 15px; font-weight: bold">{{$clie_cat->contacto}}</label><br>
                    <label style="color: #aab2b3; margin-top: 1rem">{{$clie_cat->correo}} - {{$clie_cat->telefono}}</label><br>
                </div>
            </div>
            <hr>
            <div class="row-span-1 mt-2 mb-2" style="display: grid; grid-template-columns: repeat(4, 1fr);">
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 1rem">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Importe de Ventas
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($ventas,2)}}</label>
                </div>
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 1rem">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Pagos Totales
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($pagos,2)}}</label>
                </div>
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 1rem;">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Saldo Actual
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($saldo,2)}}</label>
                </div>
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 1rem;background-color: #fc7b35; color: white">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Importe Vencido
                    </label>
                    <label style="margin-left: 1rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($vencido,2)}} <label style="font-size: 20px">({{number_format($porcentaje,2).' %'}})</label></label>
                </div>
            </div>
        </div>
    </div>
    <div class="row" style="margin-top: 2rem">
        <table>
            <thead>
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
