<script src="https://cdn.tailwindcss.com"></script>
<?php
    $empresa = \App\Models\Team::where('id',$team_id)->first();
    $fiscales = \App\Models\DatosFiscales::where('team_id',$team_id)->first();
    $mostrarClave = $fiscales?->mostrar_clave_partidas ?? true;
    $logoAncho = (int) ($fiscales?->logo_ancho ?? 200);
    $docto = \App\Models\Cotizaciones::where('id',$idcotiza)->first();
    $part = \App\Models\CotizacionesPartidas::where('cotizaciones_id',$docto->id)->get();
    $clavesInventario = \App\Models\Inventario::whereIn('id', $part->pluck('item')->filter()->unique()->values())
        ->pluck('clave', 'id');
    $clie = \App\Models\Clientes::where('id',$clie_id)->first();
    $hoy = \Carbon\Carbon::now()->format('d-m-Y');
    $vendedor = \Filament\Facades\Filament::auth()->user()->name;
?>
<style>
    .salto-pagina { page-break-after: always; }
</style>

<div class="bg-slate-100">
    <div class="max-w-4xl mx-auto bg-white p-4 rounded-xl shadow text-slate-800 text-sm">

        <!-- Encabezado -->
        <header class="flex justify-between items-start border-b pb-2 mb-2">
            <div class="flex items-center gap-2">
                @if($fiscales && $fiscales->logo64)
                    <div class="max-h-12 overflow-hidden" style="max-width: {{$logoAncho}}px;">
                        <img src="{{$fiscales->logo64}}" alt="Logo" class="h-auto w-full object-contain" style="width: {{$logoAncho}}px;">
                    </div>
                @else
                    <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-xs">
                        TI
                    </div>
                @endif
                <div>
                    <h1 class="text-lg font-bold">
                        COTIZACIÓN
                    </h1>
                    <p class="text-[10px] text-slate-500">
                        {{$empresa->name}}
                    </p>
                </div>
            </div>
            <div class="text-right text-[11px] text-slate-600">
                <p><span class="font-semibold">Folio:</span> {{$docto->docto}}</p>
                <p><span class="font-semibold">Fecha emisión:</span> {{$hoy}}</p>
            </div>
        </header>

        <!-- Datos empresa y cliente -->
        <section class="grid grid-cols-2 gap-2 mb-2 text-[11px]">
            <div class="border border-slate-200 rounded-lg p-2">
                <p class="text-[10px] font-semibold text-slate-500 mb-0.5 uppercase">DATOS DE LA EMPRESA</p>
                <p class="font-semibold text-slate-800">{{$empresa->name}}</p>
                <p>{{$fiscales->nombre}}</p>
                <p>{{$fiscales->direccion}}</p>
                <p>{{$fiscales->codigo}}</p>
                <p>Tel: {{$fiscales->telefono}} | Correo: {{$fiscales->correo}}</p>
            </div>
            <div class="border border-slate-200 rounded-lg p-2">
                <p class="text-[10px] font-semibold text-slate-500 mb-0.5 uppercase">CLIENTE</p>
                <p class="font-semibold text-slate-800">{{$clie->nombre}}</p>
                <p>{{$clie->rfc}}</p>
                <p>{{$clie->direccion}}</p>
                <p>Atención: {{$clie->contacto}}</p>
                <p>Correo: {{$clie->correo}} | Tel: {{$clie->telefono}}</p>
            </div>
        </section>

        <!-- Datos de entrega / condiciones comerciales -->
        <section class="grid grid-cols-2 gap-2 mb-2 text-[11px]">
            <div class="border border-slate-200 rounded-lg p-2">
                <p class="text-[10px] font-semibold text-slate-500 mb-0.5 uppercase">DATOS DE ENTREGA</p>
                <div class="grid grid-cols-2 gap-x-2">
                    <p><span class="font-semibold">Lugar:</span> {{$docto->entrega_lugar}}</p>
                    <p><span class="font-semibold">Horario:</span> {{$docto->entrega_horario}}</p>
                    <p class="col-span-2"><span class="font-semibold">Dirección:</span> {{$docto->entrega_direccion}}</p>
                    <p><span class="font-semibold">Contacto:</span> {{$docto->entrega_contacto}}</p>
                    <p><span class="font-semibold">Teléfono:</span> {{$docto->entrega_telefono}}</p>
                </div>
            </div>
            <div class="border border-slate-200 rounded-lg p-2">
                <p class="text-[10px] font-semibold text-slate-500 mb-0.5 uppercase">CONDICIONES COMERCIALES</p>
                <div class="grid grid-cols-2 gap-x-2">
                    <p><span class="font-semibold">Moneda:</span> {{$docto->moneda}}</p>
                    <p><span class="font-semibold">Ejecutivo:</span> {{$vendedor}}</p>
                    <p class="col-span-2"><span class="font-semibold">Condiciones de pago:</span> {{$docto->condiciones_pago}}</p>
                    <p class="col-span-2"><span class="font-semibold">Condiciones de entrega:</span> {{$docto->condiciones_entrega}}</p>
                    <p class="col-span-2"><span class="font-semibold">Referencia:</span> {{$docto->oc_referencia_interna}}</p>
                </div>
            </div>
        </section>

        <!-- Tabla de partidas -->
        <section class="mb-2">
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full text-[11px] border-collapse">
                    <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="py-1 px-2 text-left">#</th>
                        @if($mostrarClave)
                            <th class="py-1 px-2 text-left">Clave</th>
                        @endif
                        <th class="py-1 px-2 text-left">Descripción</th>
                        <th class="py-1 px-2 text-right">Cantidad</th>
                        <th class="py-1 px-2 text-left">Unidad</th>
                        <th class="py-1 px-2 text-right">Precio unit.</th>
                        <th class="py-1 px-2 text-right">Importe</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $count = 1;?>
                    @foreach($part as $par)
                    <tr class="border-t border-slate-100">
                        <td class="py-1 px-2 text-slate-600 text-[10px]">
                            {{$count}}
                        </td>
                        @if($mostrarClave)
                            <td class="py-1 px-2 font-mono text-slate-700 text-[10px]">
                                <?php
                                    $inv_par = $clavesInventario->get($par->item, '');
                                ?>
                                {{$inv_par}}
                            </td>
                        @endif
                        <td class="py-1 px-2 text-slate-800 leading-tight">
                            {{$par->descripcion}}
                        </td>
                        <td class="py-1 px-2 text-right">
                            {{number_format($par->cant,2)}}
                        </td>
                        <td class="py-1 px-2 text-left">
                            {{$par->unidad}}
                        </td>
                        <td class="py-1 px-2 text-right">
                            {{'$'.number_format($par->precio,2)}}
                        </td>
                        <td class="py-1 px-2 text-right font-semibold">
                            {{'$'.number_format($par->subtotal,2)}}
                        </td>
                    </tr>
                    @if($par->observa)
                        <tr class="bg-slate-50/50">
                            <td colspan="{{ $mostrarClave ? 7 : 6 }}" class="py-0.5 px-2 text-slate-500 italic text-[10px]">
                                <span class="font-semibold not-italic">Obs:</span> {{$par->observa}}
                            </td>
                        </tr>
                    @endif
                    <?php $count++; ?>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Totales y Observaciones -->
        <section class="grid grid-cols-12 gap-4 mb-2">
            <!-- Columna Izquierda: Observaciones y Datos Bancarios -->
            <div class="col-span-7 space-y-2">
                @if($docto->observa)
                <div class="text-[11px]">
                    <p class="text-[10px] font-semibold text-slate-600 mb-0.5 uppercase">OBSERVACIONES GENERALES</p>
                    <div class="border border-slate-200 rounded-lg p-2 text-slate-600 leading-tight min-h-[40px]">
                        {{$docto->observa}}
                    </div>
                </div>
                @endif

                @if($fiscales->banco != '')
                <div class="border border-slate-200 rounded-lg p-2 text-[10px]">
                    <p class="font-semibold text-slate-500 mb-0.5 uppercase text-[9px]">REFERENCIAS BANCARIAS</p>
                    <p><span class="font-semibold text-slate-700">Banco:</span> {{$fiscales->banco}} | <span class="font-semibold text-slate-700">Cuenta:</span> {{$fiscales->cuenta}}</p>
                    <p><span class="font-semibold text-slate-700">CLABE:</span> {{$fiscales->clabe}} | <span class="font-semibold text-slate-700">Beneficiario:</span> {{$fiscales->beneficiario}}</p>
                </div>
                @endif
            </div>

            <!-- Columna Derecha: Totales -->
            <div class="col-span-5 text-[11px]">
                <div class="flex justify-between py-0.5">
                    <span class="text-slate-600">Subtotal:</span>
                    <span class="font-semibold text-slate-800">{{'$'.number_format($docto->subtotal,2)}}</span>
                </div>
                <div class="flex justify-between py-0.5">
                    <span class="text-slate-600">IVA:</span>
                    <span class="font-semibold text-slate-800">{{'$'.number_format($docto->iva,2)}}</span>
                </div>
                <div class="flex justify-between py-1 border-t mt-1 pt-1">
                    <span class="text-slate-700 font-semibold uppercase">TOTAL:</span>
                    <span class="font-bold text-slate-900 text-base">{{'$'.number_format($docto->total,2)}}</span>
                </div>
                <p class="mt-1 text-[10px] text-slate-500 leading-tight">
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
                    <span class="font-semibold">Letra:</span> {{$cant_letras}}
                </p>
            </div>
        </section>

        <!-- Firmas -->
        <section class="grid grid-cols-2 gap-8 text-[11px] mt-4 mb-4">
            <div class="text-center">
                <div class="border-t border-slate-300 pt-1">
                    <p class="font-semibold uppercase">{{$docto->nombre_elaboro ?: '____________________'}}</p>
                    <p class="text-slate-500 text-[10px]">Elaboró</p>
                </div>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-300 pt-1">
                    <p class="font-semibold uppercase">{{$docto->nombre_autorizo ?: '____________________'}}</p>
                    <p class="text-slate-500 text-[10px]">Autorizó</p>
                </div>
            </div>
        </section>

        <!-- Leyenda -->
        @if($fiscales->leyenda_cotizaciones)
            <section class="mb-4 text-[11px] border border-slate-200 rounded-lg p-2">
                <p class="text-[10px] font-semibold text-slate-500 mb-0.5 uppercase">NOTAS ADICIONALES</p>
                <div class="text-slate-600 leading-tight">
                    {!! nl2br(e($fiscales->leyenda_cotizaciones)) !!}
                </div>
            </section>
        @endif

        <!-- Footer -->
        <footer class="pt-2 border-t text-[10px] text-slate-400 flex justify-between items-end">
            <div>
                <p>Esta cotización tiene una vigencia de 15 días naturales.</p>
                <p>Documento generado por Tus-Impuestos.</p>
            </div>
            <div class="text-right">
                <p>{{$fiscales->correo}}</p>
            </div>
        </footer>
    </div>
</div>
