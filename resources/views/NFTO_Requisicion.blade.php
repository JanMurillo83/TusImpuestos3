<script src="https://cdn.tailwindcss.com"></script>
<?php
    $empresa = \App\Models\Team::where('id',$team_id)->first();
    $fiscales = \App\Models\DatosFiscales::where('team_id',$team_id)->first();
    $docto = \App\Models\Requisiciones::where('id',$idrequisicion)->first();
    $part = \App\Models\RequisicionesPartidas::where('requisiciones_id',$docto->id)->get();
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
                        REQUISICIÓN DE COMPRA
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{$empresa->name}}
                    </p>
                </div>
            </div>
            <div class="text-right text-[13px] text-slate-600 space-y-1">
                <p><span class="font-semibold">Folio:</span> {{ ($docto->serie ?? '') . ($docto->folio ?? '') }}</p>
                <p><span class="font-semibold">Fecha emisión:</span> {{$docto->fecha}}</p>
                <p><span class="font-semibold">Proyecto:</span> {{ \App\Models\Proyectos::find($docto->proyecto)->descripcion ?? '' }}</p>
            </div>
        </header>

        <!-- Datos empresa y proveedor -->
        <section class="grid grid-cols-2 gap-4 mb-4 text-[13px]">
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">DATOS DE LA EMPRESA</p>
                <p class="font-semibold text-slate-800">{{$empresa->name}}</p>
                @if($fiscales)
                    <p>{{$fiscales->nombre}}</p>
                    <p>{{$fiscales->direccion}}</p>
                    <p>{{$fiscales->codigo}}</p>
                    <p>Tel: {{$fiscales->telefono}}</p>
                    <p>Correo: {{$fiscales->correo}}</p>
                @endif
            </div>
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">PROVEEDOR SUGERIDO</p>
                @if($prove)
                    <p class="font-semibold text-slate-800">{{$prove->nombre}}</p>
                    <p>{{$prove->rfc}}</p>
                    <p>{{$prove->direccion}}</p>
                    <p>Atención: {{$prove->contacto}}</p>
                    <p>Correo: {{$prove->correo}}</p>
                    <p>Tel: {{$prove->telefono}}</p>
                @else
                    <p class="text-slate-400 italic">No especificado</p>
                @endif
            </div>
        </section>

        <!-- Datos de solicitud -->
        <section class="mb-4 text-[13px]">
            <div class="border border-slate-200 rounded-lg p-3">
                <p class="text-[12px] font-semibold text-slate-500 mb-1">INFORMACIÓN ADICIONAL</p>
                <div class="grid grid-cols-2 gap-4">
                    <p><span class="font-semibold">Solicita:</span> {{$docto->solicita}}</p>
                    <p><span class="font-semibold">Estado:</span> {{$docto->estado}}</p>
                    <p><span class="font-semibold">Moneda:</span> {{$docto->moneda}}</p>
                </div>
            </div>
        </section>

        <!-- Tabla de partidas -->
        <section class="mb-4">
            <p class="text-[13px] text-slate-500 mb-1">
                Detalle de productos / servicios requeridos:
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
                        <th class="py-2 px-2 text-right">Costo Est.</th>
                        <th class="py-2 px-2 text-right">Importe</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $count = 1;?>
                    @foreach($part as $par)
                    <tr class="border-t border-slate-100">
                        <td class="py-1.5 px-2 text-slate-600">
                            {{$count++}}
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
                    @if($par->observa)
                        <tr class="bg-slate-50/50">
                            <td colspan="7" class="py-1 px-4 text-[12px] text-slate-500 italic">
                                Nota: {{$par->observa}}
                            </td>
                        </tr>
                    @endif
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
                    <span class="text-slate-600">IVA:</span>
                    <span class="font-semibold text-slate-800">{{'$'.number_format($docto->iva,2)}}</span>
                </div>
                <div class="flex justify-between py-1 border-t mt-1 pt-2">
                    <span class="text-slate-700 font-semibold">TOTAL ESTIMADO:</span>
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
        @if($docto->observa)
        <section class="mb-4 text-[13px]">
            <p class="text-[12px] font-semibold text-slate-600 mb-1">OBSERVACIONES</p>
            <p class="border border-slate-200 rounded-lg p-3 min-h-[40px] text-slate-600 leading-snug">
                {{$docto->observa}}
            </p>
        </section>
        @endif

        <!-- Firmas -->
        <section class="grid grid-cols-2 gap-8 text-[13px] mt-12">
            <div class="text-center">
                <p class="border-t border-slate-400 pt-2 font-semibold">
                    {{$docto->solicita}}
                </p>
                <p class="text-slate-500">
                    Solicitó
                </p>
            </div>
            <div class="text-center">
                <div class="h-8"></div>
                <p class="border-t border-slate-400 pt-2 font-semibold">
                    &nbsp;
                </p>
                <p class="text-slate-500">
                    Autorizó
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-12 pt-4 border-t text-[12px] text-slate-500 flex justify-between">
            <p>
                Documento generado automáticamente por Tus-Impuestos.<br>
                Requisición Interna.
            </p>
            <p class="text-right">
                Fecha de impresión: {{$hoy}}
            </p>
        </footer>
    </div>
</div>
