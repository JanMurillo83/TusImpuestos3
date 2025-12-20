<x-filament-panels::page>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/48953f55c7.js" crossorigin="anonymous"></script>
    <div class="bg-slate-300" style="margin-top: -2rem !important; margin-bottom: 2rem !important; margin-left: -1rem !important; margin-right: -1rem !important;">
    <div class="max-w-full mx-auto my-8 bg-white p-8 rounded-xl shadow">

        <!-- Header -->
        <header class="flex justify-between items-start border-b pb-4">
            <div class="flex items-center gap-3">
                <!-- Logo -->
                <div class="h-10 w-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold">
                    TI
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">
                        Estado de Cuenta - Saldos de Clientes
                    </h1>
                    <p class="text-xs text-slate-500">
                        Sistema Tus-Impuestos · Módulo de Cuentas por Cobrar
                    </p>
                </div>
            </div>
            <div class="text-right text-xs text-slate-600 space-y-1">
                <p><span class="font-semibold">Fecha de emisión:</span> 28/11/2025</p>
                <p><span class="font-semibold">Usuario:</span> admin@tus-impuestos.com</p>
            </div>
        </header>

        <!-- Botones de envío (WhatsApp y correo) -->
        <section class="mt-4 flex justify-end gap-2 text-xs print:hidden">
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

        <!-- Filtros -->
        <section class="mt-4 flex flex-wrap justify-between items-center gap-3 text-xs">
            <div class="text-slate-600">
                <p><span class="font-semibold">Periodo:</span> 01/11/2025 – 30/11/2025</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="flex items-center gap-1">
                    <label class="text-slate-500">Segmento:</label>
                    <select class="border rounded px-2 py-1 text-xs text-slate-700">
                        <option>Todos</option>
                        <option>Corporativo</option>
                        <option>Mayorista</option>
                        <option>Detallista</option>
                    </select>
                </div>
                <div class="flex items-center gap-1">
                    <label class="text-slate-500">Estatus:</label>
                    <select class="border rounded px-2 py-1 text-xs text-slate-700">
                        <option>Todos</option>
                        <option>Activo</option>
                        <option>Inactivo</option>
                        <option>Moroso</option>
                    </select>
                </div>
                <div class="flex items-center gap-1">
                    <input type="text" placeholder="Buscar cliente / RFC…" class="border rounded px-2 py-1 text-xs w-40">
                </div>
            </div>
        </section>

        <!-- KPIs -->
        <section class="mt-5 grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">👥 Total clientes</p>
                <p class="text-lg font-semibold text-slate-800 mt-1">125</p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">💰 Saldo total cartera</p>
                <p class="text-lg font-semibold text-slate-800 mt-1">$ 6,533,301.29</p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">⚠️ Saldo vencido</p>
                <p class="text-lg font-semibold text-red-600 mt-1">$ 980,000.00</p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <p class="text-xs text-slate-500">⏳ Saldo por vencer</p>
                <p class="text-lg font-semibold text-slate-800 mt-1">$ 5,553,301.29</p>
            </div>
            <div class="bg-orange-500 text-white rounded-lg p-3">
                <p class="text-xs">📊 % Cartera vencida</p>
                <p class="text-lg font-bold mt-1">15.00%</p>
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
                    <!-- Ejemplo 1 -->
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 px-2 font-semibold text-slate-800">
                            SHAPE CORP. MEXICO
                        </td>
                        <td class="py-1.5 px-2 text-slate-600">
                            RFC_SHAPE
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            $ 0.00
                        </td>
                        <td class="py-1.5 px-2 text-right font-semibold">
                            $ 1,775,977.93
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            27.18%
                        </td>
                        <td class="py-1.5 px-2 text-right text-red-600">
                            <!-- saldo vencido calculado -->
                            $ 0.00
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            <!-- saldo por vencer calculado -->
                            $ 1,775,977.93
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <a
                                href="#"
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

                    <!-- Ejemplo 2 -->
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <td class="py-1.5 px-2 font-semibold text-slate-800">
                            TERNIUM MEXICO
                        </td>
                        <td class="py-1.5 px-2 text-slate-600">
                            RFC_TERNIUM
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            $ 0.00
                        </td>
                        <td class="py-1.5 px-2 text-right font-semibold">
                            $ 1,469,908.47
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            22.50%
                        </td>
                        <td class="py-1.5 px-2 text-right text-red-600">
                            $ 0.00
                        </td>
                        <td class="py-1.5 px-2 text-right">
                            $ 1,469,908.47
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <a href="#" class="text-sky-700 hover:underline font-semibold">
                                Ver detalle
                            </a>
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <a href="#" class="inline-flex items-center px-2 py-1 rounded-md bg-sky-600 hover:bg-sky-700 text-white font-semibold">
                                ✉️
                            </a>
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <a href="#" class="inline-flex items-center px-2 py-1 rounded-md bg-green-500 hover:bg-green-600 text-white font-semibold">
                                📲
                            </a>
                        </td>
                    </tr>

                    <!-- Aquí tu sistema iteraría el resto de clientes -->
                    </tbody>

                    <!-- Totales -->
                    <tfoot>
                    <tr class="bg-slate-100 font-semibold text-slate-800">
                        <td class="py-2 px-2 text-left" colspan="3">
                            TOTAL CARTERA
                        </td>
                        <td class="py-2 px-2 text-right">
                            $ 6,533,301.29
                        </td>
                        <td class="py-2 px-2 text-right">
                            100.00%
                        </td>
                        <td class="py-2 px-2 text-right text-red-600">
                            <!-- total vencido -->
                            $ 980,000.00
                        </td>
                        <td class="py-2 px-2 text-right">
                            <!-- total por vencer -->
                            $ 5,553,301.29
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
                Departamento de Crédito y Cobranza<br>
                cobranza@tus-impuestos.com · (442) 000 00 00
            </p>
        </footer>
    </div>
    </div>
</x-filament-panels::page>
