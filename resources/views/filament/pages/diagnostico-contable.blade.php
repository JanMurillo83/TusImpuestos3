<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $hayErrores = false;
            foreach ($resultados as $seccion => $data) {
                if (!empty($data)) {
                    $hayErrores = true;
                    break;
                }
            }
        @endphp

        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Resumen de Auditoría</h2>
            <x-filament::button wire:click="ejecutarDiagnostico" icon="heroicon-m-arrow-path">
                Actualizar Diagnóstico
            </x-filament::button>
        </div>

        @if (!$hayErrores)
            <x-filament::section>
                <div class="flex flex-col items-center justify-center p-6 text-center">
                    <div class="rounded-full bg-green-100 p-3 mb-4">
                        <x-heroicon-o-check-circle class="w-12 h-12 text-green-600" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Sin inconsistencias detectadas</h3>
                    <p class="text-gray-500">La contabilidad parece estar en orden según las validaciones automáticas.</p>
                </div>
            </x-filament::section>
        @else
            <!-- 1. Pólizas descuadradas -->
            @if(!empty($resultados['polizas_descuadradas']))
                <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
                    <x-slot name="heading">Pólizas Descuadradas</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">ID Póliza</th>
                                    <th class="p-2 border dark:border-gray-700">Fecha</th>
                                    <th class="p-2 border dark:border-gray-700">Tipo/Folio</th>
                                    <th class="p-2 border dark:border-gray-700">Periodo</th>
                                    <th class="p-2 border dark:border-gray-700">Ejercicio</th>
                                    <th class="p-2 border dark:border-gray-700">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['polizas_descuadradas'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->poliza_id }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->tipo }}-{{ $row->folio }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->periodo }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->ejercicio }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-red-600 font-bold">$ {{ number_format($row->diferencia, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 2. Movimientos sin cuenta válida -->
            @if(!empty($resultados['movimientos_sin_cuenta']))
                <x-filament::section icon="heroicon-o-no-symbol" icon-color="danger">
                    <x-slot name="heading">Movimientos sin Cuenta Válida</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">Póliza</th>
                                    <th class="p-2 border dark:border-gray-700">Periodo</th>
                                    <th class="p-2 border dark:border-gray-700">Ejercicio</th>
                                    <th class="p-2 border dark:border-gray-700">Movimiento ID</th>
                                    <th class="p-2 border dark:border-gray-700">Cuenta Referenciada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['movimientos_sin_cuenta'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->tipo }}-{{ $row->folio }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->periodo }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->ejercicio }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->movimiento_id }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-red-600 font-mono">{{ $row->cuenta_referenciada ?: 'NULL' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 3. Cuentas contables duplicadas -->
            @if(!empty($resultados['cuentas_duplicadas']))
                <x-filament::section icon="heroicon-o-square-2-stack" icon-color="warning">
                    <x-slot name="heading">Cuentas Contables Duplicadas</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">Cuenta</th>
                                    <th class="p-2 border dark:border-gray-700">IDs Duplicados</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['cuentas_duplicadas'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700 font-mono">{{ $row->cuenta }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->ids_duplicados }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 4. Naturaleza incorrecta -->
            @if(!empty($resultados['naturaleza_incorrecta']))
                <x-filament::section icon="heroicon-o-arrows-up-down" icon-color="warning">
                    <x-slot name="heading">Naturalezas Inconsistentes</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">Cuenta</th>
                                    <th class="p-2 border dark:border-gray-700">Naturaleza</th>
                                    <th class="p-2 border dark:border-gray-700">Saldo Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['naturaleza_incorrecta'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700 font-mono">{{ $row->cuenta }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->naturaleza == 'D' ? 'Deudora' : 'Acreedora' }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-red-600 font-bold">$ {{ number_format($row->saldo, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 5. Movimientos huérfanos -->
            @if(!empty($resultados['movimientos_huerfanos']))
                <x-filament::section icon="heroicon-o-puzzle-piece" icon-color="danger">
                    <x-slot name="heading">Movimientos Huérfanos</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">ID Movimiento</th>
                                    <th class="p-2 border dark:border-gray-700">ID Póliza Referenciada</th>
                                    <th class="p-2 border dark:border-gray-700">Cuenta</th>
                                    <th class="p-2 border dark:border-gray-700">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['movimientos_huerfanos'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->id }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->poliza_id }}</td>
                                        <td class="p-2 border dark:border-gray-700 font-mono">{{ $row->codigo }}</td>
                                        <td class="p-2 border dark:border-gray-700">$ {{ number_format($row->cargo ?: $row->abono, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 6. Acumulación incorrecta -->
            @if(!empty($resultados['problemas_acumulacion']))
                <x-filament::section icon="heroicon-o-calculator" icon-color="danger">
                    <x-slot name="heading">Problemas de Acumulación</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">Cuenta</th>
                                    <th class="p-2 border dark:border-gray-700">Saldo Calculado</th>
                                    <th class="p-2 border dark:border-gray-700">Saldo Almacenado</th>
                                    <th class="p-2 border dark:border-gray-700">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['problemas_acumulacion'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700 font-mono">{{ $row->cuenta }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-right font-bold">$ {{ number_format($row->saldo_calculado, 2) }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-right font-bold">$ {{ number_format($row->saldo_almacenado, 2) }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-right text-red-600 font-bold">$ {{ number_format($row->diferencia, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 7. Movimientos con importes inválidos -->
            @if(!empty($resultados['importes_invalidos']))
                <x-filament::section icon="heroicon-o-no-symbol" icon-color="danger">
                    <x-slot name="heading">Movimientos Inválidos (Importes)</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">ID Mov</th>
                                    <th class="p-2 border dark:border-gray-700">Póliza</th>
                                    <th class="p-2 border dark:border-gray-700">Periodo</th>
                                    <th class="p-2 border dark:border-gray-700">Ejercicio</th>
                                    <th class="p-2 border dark:border-gray-700">Cargo</th>
                                    <th class="p-2 border dark:border-gray-700">Abono</th>
                                    <th class="p-2 border dark:border-gray-700">Razón</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['importes_invalidos'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->id }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->tipo }}-{{ $row->folio }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->periodo }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->ejercicio }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ number_format($row->cargo, 2) }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ number_format($row->abono, 2) }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-red-600">
                                            {{ ($row->cargo == 0 && $row->abono == 0) ? 'Ambos en cero' : 'Ambos con valor' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            <!-- 8. Cuentas inexistentes en jerarquía -->
            @if(!empty($resultados['jerarquia_inexistente']))
                <x-filament::section icon="heroicon-o-list-bullet" icon-color="warning">
                    <x-slot name="heading">Jerarquía de Cuentas Inconsistente</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 border dark:border-gray-700">Cuenta Hija</th>
                                    <th class="p-2 border dark:border-gray-700">Nombre</th>
                                    <th class="p-2 border dark:border-gray-700">Padre Faltante</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados['jerarquia_inexistente'] as $row)
                                    <tr>
                                        <td class="p-2 border dark:border-gray-700 font-mono">{{ $row->cuenta }}</td>
                                        <td class="p-2 border dark:border-gray-700">{{ $row->nombre }}</td>
                                        <td class="p-2 border dark:border-gray-700 text-red-600 font-mono">{{ $row->padre_esperado }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif

        <!-- SQL Queries Appendix -->
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Consultas SQL de Referencia (Auditoría)</x-slot>
            <div class="space-y-4 font-mono text-xs">
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 1. Pólizas descuadradas</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT p.id, p.fecha, p.tipo, p.folio, p.periodo, p.ejercicio, SUM(a.cargo) as cargos, SUM(a.abono) as abonos FROM auxiliares a JOIN cat_polizas p ON a.cat_polizas_id = p.id WHERE a.team_id = :team_id GROUP BY p.id, p.fecha, p.tipo, p.folio, p.periodo, p.ejercicio HAVING ABS(SUM(a.cargo) - SUM(a.abono)) > 0.01;</pre>
                </div>
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 2. Movimientos sin cuenta válida</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT a.* FROM auxiliares a LEFT JOIN cat_cuentas c ON a.codigo = c.codigo AND a.team_id = c.team_id WHERE a.team_id = :team_id AND c.id IS NULL;</pre>
                </div>
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 3. Cuentas duplicadas</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT codigo, COUNT(*) FROM cat_cuentas WHERE team_id = :team_id GROUP BY codigo HAVING COUNT(*) > 1;</pre>
                </div>
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 4. Naturaleza incorrecta</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT * FROM saldoscuentas WHERE team_id = :team_id AND ((naturaleza = 'D' AND s12 < 0) OR (naturaleza = 'A' AND s12 > 0));</pre>
                </div>
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 5. Movimientos huérfanos</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT a.* FROM auxiliares a LEFT JOIN cat_polizas p ON a.cat_polizas_id = p.id WHERE a.team_id = :team_id AND p.id IS NULL;</pre>
                </div>
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 6. Error de acumulación</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT codigo, s12, (si + c1 + ... + c12 - (a1 + ... + a12)) as calculado FROM saldoscuentas WHERE team_id = :team_id HAVING ABS(s12 - calculado) > 0.01;</pre>
                </div>
                <div>
                    <p class="font-bold text-gray-700 mb-1">-- 7. Importes inválidos</p>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">SELECT * FROM auxiliares WHERE team_id = :team_id AND ((cargo = 0 AND abono = 0) OR (cargo != 0 AND abono != 0));</pre>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
