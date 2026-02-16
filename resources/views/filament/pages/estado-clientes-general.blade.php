<x-filament-panels::page>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
    <div class="bg-slate-300" style="margin-top: -2rem !important; margin-bottom: 2rem !important; margin-left: -1rem !important; margin-right: -1rem !important;">
    <div class="max-w-full mx-auto my-8 bg-white p-8 rounded-xl shadow">

        <!-- Header -->
        <header class="flex justify-between items-start border-b pb-4">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">
                        Estado de Cuenta - Saldos de Clientes
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{$empresa}} · Módulo de Cuentas por Cobrar
                    </p>
                </div>
            </div>
            <div class="text-right text-xs text-slate-600 space-y-1">
                <p><span class="font-semibold">Fecha de emisión:</span> {{\Carbon\Carbon::now()->format('d-m-Y')}}</p>
                <p><span class="font-semibold">Usuario:</span> {{\Illuminate\Support\Facades\Auth::user()->name}}</p>
            </div>
        </header>

        <!-- Botones de exportación y envío -->
        <section class="mt-4 flex justify-end gap-2 text-xs print:hidden">
            <!-- Exportar a PDF -->
            <button
                wire:click="exportarPDF"
                class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white font-semibold"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span>Exportar a PDF</span>
            </button>

            <!-- Exportar a Excel -->
            <button
                wire:click="exportarExcel"
                class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-green-700 hover:bg-green-800 text-white font-semibold"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Exportar a Excel</span>
            </button>

            <!-- WhatsApp: reemplaza TU_NUMERO y la URL del reporte de cartera -->
            <a
                href="https://wa.me/TU_NUMERO?text=Te%20comparto%20el%20reporte%20de%20saldos%20de%20clientes%20correspondiente%20al%20periodo%2001/11/2025%20-%2030/11/2025.%20Puedes%20consultarlo%20en%3A%20https%3A%2F%2Ftu-sistema.com%2Freporte%2Fcartera-clientes-2025-11"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-green-500 hover:bg-green-600 text-white font-semibold"
            >
                <span>📲</span>
                <span>Enviar por WhatsApp</span>
            </a>

            <!-- Correo: mailto con asunto y cuerpo -->
            <a
                href="mailto:?subject=Reporte%20de%20saldos%20de%20clientes&body=Te%20comparto%20el%20reporte%20de%20saldos%20de%20clientes%20correspondiente%20al%20periodo%2001/11/2025%20-%2030/11/2025.%0APuedes%20consultarlo%20en:%20https://tu-sistema.com/reporte/cartera-clientes-2025-11"
                class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-semibold"
            >
                <span>✉️</span>
                <span>Enviar por correo</span>
            </a>
        </section>

        <section class="mt-5 grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">👥 Total clientes</p>
                <p class="text-lg font-semibold text-slate-800 mt-1">{{intval(count($maindata))}}</p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">💰 Saldo total cartera</p>
                <p class="text-lg font-semibold text-slate-800 mt-1">{{'$'.number_format($saldo_total,2)}}</p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">⚠️ Saldo vencido</p>
                <p class="text-lg font-semibold text-red-600 mt-1">{{'$'.number_format($saldo_vencido,2)}}</p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">⏳ Saldo por vencer</p>
                <p class="text-lg font-semibold text-slate-800 mt-1">{{'$'.number_format($saldo_corriente,2)}}</p>
            </div>
            <div class="bg-orange-500 text-white rounded-lg p-3">
                <p class="text-xs">📊 % Cartera vencida</p>
                <?php
                    $por_in = $saldo_vencido * 100 / max($saldo_total,1);
                ?>
                <p class="text-lg font-bold mt-1">{{number_format($por_in,2).'%'}}</p>
            </div>
        </section>

        <!-- Tabla de saldos por cliente -->
        <section class="mt-6">
            <div class="flex justify-between items-center mb-2 text-xs text-slate-500">
                <p>Detalle por cliente</p>
                <p>Montos en MXN</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[11px] border-collapse">
                    <thead>
                    <tr class="bg-slate-100 text-slate-700">
                        <th class="py-2 px-2 text-left">Cliente</th>
                        <th class="py-2 px-2 text-left">RFC</th>
                        <th class="py-2 px-2 text-right">Límite de crédito</th>
                        <th class="py-2 px-2 text-right">Saldo TOTAL</th>
                        <th class="py-2 px-2 text-right">% de cobranza</th>
                        <th class="py-2 px-2 text-right">Saldo VENCIDO</th>
                        <th class="py-2 px-2 text-right">Saldo POR VENCER</th>
                        <th class="py-2 px-2 text-center">Detalle</th>
                        <th class="py-2 px-2 text-center">Correo</th>
                        <th class="py-2 px-2 text-center">WhatsApp</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        $total_sdo_det = 0;
                        $total_por_det = 0;
                        $total_ven_det = 0;
                        $total_cor_det = 0;
                    ?>
                    @foreach($maindata as $data)
                        <?php
                            $cl_data = \App\Models\Clientes::where('cuenta_contable',$data->clave)
                            ->first();
                            $rfc_cl = $cl_data?->rfc ?? 'XAXX010101000';
                            $lim_cl = floatval($cl_data?->limite_credito ?? 0);
                            $por_det = $data->saldo * 100 / max($saldo_total,1);
                            $total_sdo_det+= floatval($data->saldo);
                            $total_por_det+= $por_det;
                            $total_ven_det+= floatval($data->vencido);
                            $total_cor_det+= floatval($data->corriente);
                        ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-1.5 px-2 font-semibold text-slate-800">
                                {{$data->cliente}}
                            </td>
                            <td class="py-1.5 px-2 text-slate-600">
                                {{$rfc_cl}}
                            </td>
                            <td class="py-1.5 px-2 text-right text-slate-600">
                                {{'$'.number_format($lim_cl,2)}}
                            </td>
                            <td class="py-1.5 px-2 text-right font-semibold text-slate-600">
                                {{'$'.number_format($data->saldo,2)}}
                            </td>
                            <td class="py-1.5 px-2 text-right text-slate-600">
                                {{number_format($por_det,2).'%'}}
                            </td>
                            <td class="py-1.5 px-2 text-right text-red-600">
                                <!-- saldo vencido calculado -->
                                {{'$'.number_format($data->vencido,2)}}
                            </td>
                            <td class="py-1.5 px-2 text-right text-slate-600">
                                <!-- saldo por vencer calculado -->
                                {{'$'.number_format($data->corriente,2)}}
                            </td>
                            <td class="py-1.5 px-2 text-center ">
                                <a
                                    href="{{\App\Filament\Pages\EstadoClientesDetalle::getUrl(['cliente'=>$data->clave])}}"
                                    class="text-sky-700 hover:underline font-semibold"
                                >
                                    Ver detalle
                                </a>
                            </td>
                            <td class="py-1.5 px-2 text-center">
                                <a
                                    href="#"
                                    class="inline-flex items-center px-2 py-1 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-semibold"
                                >
                                    ✉️
                                </a>
                            </td>
                            <td class="py-1.5 px-2 text-center">
                                <a
                                    href="#"
                                    class="inline-flex items-center px-2 py-1 rounded-md bg-green-500 hover:bg-green-600 text-white font-semibold"
                                >
                                    📲
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="bg-slate-100 font-semibold text-slate-800">
                        <td class="py-2 px-2 text-left" colspan="3">
                            TOTAL CARTERA
                        </td>
                        <td class="py-2 px-2 text-right">
                            {{'$'.number_format($total_sdo_det,2)}}
                        </td>
                        <td class="py-2 px-2 text-right">
                            {{number_format($total_por_det,2).'%'}}
                        </td>
                        <td class="py-2 px-2 text-right text-red-600">
                            <!-- total vencido -->
                            {{'$'.number_format($total_ven_det,2)}}
                        </td>
                        <td class="py-2 px-2 text-right">
                            <!-- total por vencer -->
                            {{'$'.number_format($total_cor_det,2)}}
                        </td>
                        <td class="py-2 px-2 text-center" colspan="3">
                            <!-- espacio para nota u otras acciones -->
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-6 pt-4 border-t text-[10px] text-slate-500 flex justify-between">
            <p>
                Reporte generado automáticamente por Tus-Impuestos.com<br>
                La información está sujeta a la captura y conciliación al día de hoy.
            </p>
            <p class="text-right">
                Departamento de Cuentas por Cobrar<br>
                {{$emp_correo}} · {{$emp_telefono}}
            </p>
        </footer>
    </div>
    </div>
</x-filament-panels::page>
