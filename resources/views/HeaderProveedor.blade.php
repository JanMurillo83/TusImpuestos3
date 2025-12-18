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
            <div class="row-span-1 mt-2" style="border: #7f8c8d solid 1px;border-radius: 20px; padding: 1rem; background-color: #f0f3f7">
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
                <div class="col-md-6" style="text-align: right;margin-top: -3rem">
                    <button type="button"
                            wire:click="mountAction('pdf')">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="red" class="icon2">
                            <path fill-rule="evenodd" d="M7.875 1.5C6.839 1.5 6 2.34 6 3.375v2.99c-.426.053-.851.11-1.274.174-1.454.218-2.476 1.483-2.476 2.917v6.294a3 3 0 0 0 3 3h.27l-.155 1.705A1.875 1.875 0 0 0 7.232 22.5h9.536a1.875 1.875 0 0 0 1.867-2.045l-.155-1.705h.27a3 3 0 0 0 3-3V9.456c0-1.434-1.022-2.7-2.476-2.917A48.716 48.716 0 0 0 18 6.366V3.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM16.5 6.205v-2.83A.375.375 0 0 0 16.125 3h-8.25a.375.375 0 0 0-.375.375v2.83a49.353 49.353 0 0 1 9 0Zm-.217 8.265c.178.018.317.16.333.337l.526 5.784a.375.375 0 0 1-.374.409H7.232a.375.375 0 0 1-.374-.409l.526-5.784a.373.373 0 0 1 .333-.337 41.741 41.741 0 0 1 8.566 0Zm.967-3.97a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H18a.75.75 0 0 1-.75-.75V10.5ZM15 9.75a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V10.5a.75.75 0 0 0-.75-.75H15Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button type="button"
                            wire:click="mountAction('xls')">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="green" class="icon2">
                            <path fill-rule="evenodd" d="M9.75 6.75h-3a3 3 0 0 0-3 3v7.5a3 3 0 0 0 3 3h7.5a3 3 0 0 0 3-3v-7.5a3 3 0 0 0-3-3h-3V1.5a.75.75 0 0 0-1.5 0v5.25Zm0 0h1.5v5.69l1.72-1.72a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 1 1 1.06-1.06l1.72 1.72V6.75Z" clip-rule="evenodd" />
                            <path d="M7.151 21.75a2.999 2.999 0 0 0 2.599 1.5h7.5a3 3 0 0 0 3-3v-7.5c0-1.11-.603-2.08-1.5-2.599v7.099a4.5 4.5 0 0 1-4.5 4.5H7.151Z" />
                        </svg>
                    </button>
                    <button type="button"
                            wire:click="mountAction('mail')">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="blue" class="icon2">
                            <path d="M19.5 22.5a3 3 0 0 0 3-3v-8.174l-6.879 4.022 3.485 1.876a.75.75 0 1 1-.712 1.321l-5.683-3.06a1.5 1.5 0 0 0-1.422 0l-5.683 3.06a.75.75 0 0 1-.712-1.32l3.485-1.877L1.5 11.326V19.5a3 3 0 0 0 3 3h15Z" />
                            <path d="M1.5 9.589v-.745a3 3 0 0 1 1.578-2.642l7.5-4.038a3 3 0 0 1 2.844 0l7.5 4.038A3 3 0 0 1 22.5 8.844v.745l-8.426 4.926-.652-.351a3 3 0 0 0-2.844 0l-.652.351L1.5 9.589Z" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="row-span-1 mt-2 mb-2" style="display: grid; grid-template-columns: repeat(4, 1fr);">
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 2rem">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Importe de Ventas
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($ventas,2)}}</label>
                </div>
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 2rem">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Pagos Totales
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($pagos,2)}}</label>
                </div>
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 2rem;">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Saldo Actual
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($saldo,2)}}</label>
                </div>
                <div style="border: #4b5563 solid 1px; border-radius: 15px; margin-left: 2rem;background-color: #fc7b35; color: white">
                    <label class="iconlabel" style="margin-left: 1rem">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="icon">
                            <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 1.653.713c.426.33.744.74.925 1.2a.75.75 0 0 1-1.395.55 1.35 1.35 0 0 0-.447-.563 2.187 2.187 0 0 0-.736-.363V9.3c.698.093 1.383.32 1.959.696.787.514 1.29 1.27 1.29 2.13 0 .86-.504 1.616-1.29 2.13-.576.377-1.261.603-1.96.696v.299a.75.75 0 1 1-1.5 0v-.3c-.697-.092-1.382-.318-1.958-.695-.482-.315-.857-.717-1.078-1.188a.75.75 0 1 1 1.359-.636c.08.173.245.376.54.569.313.205.706.353 1.138.432v-2.748a3.782 3.782 0 0 1-1.653-.713C6.9 9.433 6.5 8.681 6.5 7.875c0-.805.4-1.558 1.097-2.096a3.78 3.78 0 0 1 1.653-.713V4.75A.75.75 0 0 1 10 4Z" clip-rule="evenodd" />
                        </svg>
                        Importe Vencido
                    </label>
                    <label style="margin-left: 2rem; font-size: 30px; font-weight: bold; text-align: center">{{'$'.number_format($vencido,2)}} <label style="font-size: 20px">({{number_format($porcentaje,2).' %'}})</label></label>
                </div>
            </div>
        </div>
    </div>
</div>
