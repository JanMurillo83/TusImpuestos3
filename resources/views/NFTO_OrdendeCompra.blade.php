<script src="https://cdn.tailwindcss.com"></script>
<?php
    $empresa = \App\Models\Team::where('id',$team_id)->first();
    $fiscales = \App\Models\DatosFiscales::where('team_id',$team_id)->first();
    $docto = \App\Models\Ordenes::where('id',$idorden)->first();
    $part = \App\Models\OrdenesPartidas::where('ordenes_id',$docto->id)->get();
    $prove = \App\Models\Proveedores::where('id',$prov_id)->first();
    $hoy = \Carbon\Carbon::now()->format('d-m-Y');
?>
<div class="bg-slate-100">
    <div class="max-w-3xl mx-auto my-8 bg-white p-8 rounded-xl shadow text-slate-800 text-base">

        <!-- Encabezado -->
        <header class="flex justify-between items-start border-b pb-4 mb-4">
            <div class="flex items-center gap-3">
                @if($fiscales && $fiscales->logo64)
                    <div class="max-h-16 max-w-[200px] overflow-hidden">
                        <img src="{{$fiscales->logo64}}" alt="Logo" class="h-auto w-full object-contain">
                    </div>
                @else
                    <div class="h-10 w-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold">
                        TI
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-bold">
                        ORDEN DE COMPRA
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{$empresa->name}}
                    </p>
                </div>
            </div>
            <div class="text-right text-[13px] text-slate-600 space-y-1">
                <p><span class="font-semibold">Folio OC:</span> {{$docto->folio}}</p>
                <p><span class="font-semibold">Fecha emisión:</span> {{$hoy}}</p>
                <p><span class="font-semibold">Proyecto:</span> {{ \App\Models\Proyectos::find($docto->proyecto)->descripcion ?? '' }}</p>
            </div>
        </header>

        <!-- Datos empresa y proveedor -->
        <section class="grid grid-cols-2 gap-4 mb-4 text-[13px]">
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">DATOS DE LA EMPRESA</p>
                <p class="font-semibold text-slate-800">{{$empresa->name}}</p>
                <p>{{$fiscales->nombre}}</p>
                <p>{{$fiscales->direccion}}</p>
                <p>{{$fiscales->codigo}}</p>
                <p>Tel: {{$fiscales->telefono}}</p>
                <p>Correo: {{$fiscales->correo}}</p>
            </div>
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">PROVEEDOR</p>
                <p class="font-semibold text-slate-800">{{$prove->nombre}}</p>
                <p>{{$prove->rfc}}</p>
                <p>{{$prove->direccion}}</p>
                <p>Atención: {{$prove->contacto}}</p>
                <p>Correo: {{$prove->correo}}</p>
                <p>Tel: {{$prove->telefono}}</p>
            </div>
        </section>

        <!-- Datos de entrega / forma de pago -->
        <section class="grid grid-cols-2 gap-4 mb-4 text-[13px]">
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">ENTREGA</p>
                <p><span class="font-semibold">Lugar:</span> {{$docto->entrega_lugar}}</p>
                <p><span class="font-semibold">Dirección:</span> {{$docto->entrega_direccion}}</p>
                <p><span class="font-semibold">Horario recepción:</span> {{$docto->entrega_horario}}</p>
                <p><span class="font-semibold">Contacto en almacén:</span> {{$docto->entrega_contacto}}</p>
                <p><span class="font-semibold">Teléfono almacén:</span> {{$docto->entrega_telefono}}</p>
            </div>
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">CONDICIONES COMERCIALES</p>
                <p><span class="font-semibold">Moneda:</span> {{$docto->moneda}}</p>
                <p><span class="font-semibold">Condiciones de pago:</span> {{$docto->condiciones_pago}}</p>
                <p><span class="font-semibold">Días de crédito:</span> {{$prove->dias_credito}}</p>
                <p><span class="font-semibold">Incoterm / Entrega:</span> {{$docto->condiciones_entrega}}</p>
                <p><span class="font-semibold">Referencia interna:</span> {{$docto->oc_referencia_interna}}</p>
            </div>
        </section>

        <!-- Tabla de partidas -->
        <section class="mb-4">
            <p class="text-[13px] text-slate-500 mb-1">
                Detalle de productos / servicios solicitados:
            </p>
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full text-[13px] border-collapse">
                    <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="py-2 px-2 text-left">#</th>
                        <th class="py-2 px-2 text-left">SKU</th>
                        <th class="py-2 px-2 text-left">Descripción</th>
                        <th class="py-2 px-2 text-right">Cantidad</th>
                        <th class="py-2 px-2 text-left">Unidad</th>
                        <th class="py-2 px-2 text-right">Precio unit.</th>
                        <th class="py-2 px-2 text-right">Importe</th>
                        <th class="py-2 px-2 text-left">Notas</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $count = 1;?>
                    @foreach($part as $par)
                    <tr class="border-t border-slate-100">
                        <td class="py-1.5 px-2 text-slate-600">
                            {{$count}}
                        </td>
                        <td class="py-1.5 px-2 font-mono text-slate-700">
                            {{$par->item}}
                        </td>
                        <td class="py-1.5 px-2 text-slate-800">
                            {{$par->descripcion}}
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            {{number_format($par->cant,2)}}
                        </td>
                        <td class="py-1.5 px-2 text-left">
                            {{$par->unidad}}
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            {{'$'.number_format($par->costo,2)}}
                        </td>
                        <td class="py-1.5 px-2 text-right font-semibold">
                            {{'$'.number_format($par->subtotal,2)}}
                        </td>
                    </tr>
                        <tr>
                            <td colspan="7" class="py-1.5 px-2 text-slate-600">
                                {{$par->observa}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Totales -->
        <section class="flex justify-end mb-4">
            <div class="w-full sm:w-72 text-[13px]">
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">Subtotal:</span>
                    <span class="font-semibold text-slate-800">{{'$'.number_format($docto->subtotal,2)}}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">Descuento:</span>
                    <span class="font-semibold text-slate-800">{{'$'.number_format(0,2)}}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-600">IVA:</span>
                    <span class="font-semibold text-slate-800">{{'$'.number_format($docto->iva,2)}}</span>
                </div>
                <div class="flex justify-between py-1 border-t mt-1 pt-2">
                    <span class="text-slate-700 font-semibold">TOTAL ORDEN DE COMPRA:</span>
                    <span class="font-bold text-slate-900 text-lg">{{'$'.number_format($docto->total,2)}}</span>
                </div>
                <p class="mt-2 text-[12px] text-slate-500">
                    <?php
                    $formatter = new \Luecano\NumeroALetras\NumeroALetras();
                    $cant_letras = '';

                    if($docto->moneda == 'MXN'){
                        $formatter->conector = 'PESOS';
                        $cant_letras = $formatter->toInvoice($docto->total, 2, 'M.N.');
                    }else{
                        $formatter->conector = 'DOLARES';
                        $cant_letras = $formatter->toInvoice($docto->total, 2, 'USD');
                    }
                    ?>
                    Importe con letra: {{$cant_letras}}
                </p>
            </div>
        </section>

        <!-- Observaciones -->
        <section class="mb-4 text-[13px]">
            <p class="text-[12px] font-semibold text-slate-600 mb-1">OBSERVACIONES</p>
            <p class="border border-slate-200 rounded-lg p-3 min-h-[60px] text-slate-600 leading-snug">
                {{$docto->observa}}
            </p>
        </section>

        <!-- Condiciones generales -->
        <section class="mb-6 text-[12px] text-slate-500 leading-snug">
            <p class="font-semibold mb-1">Condiciones generales:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>Esta orden de compra debe ser confirmada por escrito por el proveedor.</li>
                <li>Cualquier cambio en precios, cantidades o tiempos de entrega deberá ser autorizado previamente.</li>
                <li>El proveedor se compromete a entregar productos en tiempo, forma y calidad acordados.</li>
                <li>Las facturas deberán emitirse a nombre de {{$fiscales->nombre}} indicando el número de OC {{$docto->folio}}.</li>
            </ul>
        </section>

        <!-- Firmas -->
        <section class="grid grid-cols-2 gap-8 text-[13px] mt-6">
            <div class="text-center">
                <p class="border-t border-slate-400 pt-2 font-semibold">
                    {{$docto->nombre_elaboro}}
                </p>
                <p class="text-slate-500">
                    Elaboró
                </p>
            </div>
            <div class="text-center">
                <p class="border-t border-slate-400 pt-2 font-semibold">
                    {{$docto->nombre_autorizo}}
                </p>
                <p class="text-slate-500">
                    Autorizó
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-6 pt-4 border-t text-[12px] text-slate-500 flex justify-between">
            <p>
                Documento generado automáticamente por Tus-Impuestos.<br>
                Uso interno y para relación comercial con el proveedor.
            </p>
            <p class="text-right">
                {{$fiscales->correo}}
            </p>
        </footer>
    </div>
</div>
