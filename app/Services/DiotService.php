<?php

namespace App\Services;

use App\Models\Almacencfdis;
use App\Models\AuxCFDI;
use App\Models\Auxiliares;
use App\Models\Proveedores;
use Illuminate\Support\Facades\DB;

class DiotService
{
    /**
     * Obtiene los datos de DIOT basados en pagos efectivamente realizados
     *
     * @param int $periodo Mes (1-12)
     * @param int $ejercicio Año
     * @param int $team_id ID del equipo/empresa
     * @return array Datos agrupados por proveedor
     */
    public function obtenerDatosDiot($periodo, $ejercicio, $team_id)
    {
        // Obtener todas las pólizas de egresos del periodo
        $pagos = DB::table('auxiliares')
            ->join('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
            ->leftJoin('ingresos_egresos', 'auxiliares.igeg_id', '=', 'ingresos_egresos.id')
            ->leftJoin('almacencfdis', 'ingresos_egresos.xml_id', '=', 'almacencfdis.id')
            ->where('cat_polizas.tipo', 'Eg')
            ->where('cat_polizas.periodo', $periodo)
            ->where('cat_polizas.ejercicio', $ejercicio)
            ->where('cat_polizas.team_id', $team_id)
            ->where('auxiliares.codigo', '11801000') // IVA acreditable pagado
            ->where('auxiliares.cargo', '>', 0)
            ->whereNotNull('almacencfdis.UUID')
            ->select(
                'almacencfdis.UUID',
                'almacencfdis.Emisor_Rfc as rfc',
                'almacencfdis.Emisor_Nombre as nombre',
                'almacencfdis.id as xml_id',
                'auxiliares.cargo as iva_pagado',
                'cat_polizas.fecha as fecha_pago',
                'cat_polizas.folio as poliza'
            )
            ->get();

        // Agrupar por proveedor y sumar totales
        $proveedores_diot = [];

        foreach ($pagos as $pago) {
            $rfc = $pago->rfc;

            if (!isset($proveedores_diot[$rfc])) {
                // Obtener datos del proveedor
                $proveedor = Proveedores::where('rfc', $rfc)
                    ->where('team_id', $team_id)
                    ->first();

                $proveedores_diot[$rfc] = [
                    'rfc' => $rfc,
                    'nombre' => $pago->nombre,
                    'tipo_tercero' => $proveedor->tipo_tercero ?? '04', // Nacional por defecto
                    'tipo_operacion' => $proveedor->tipo_operacion ?? '85', // Adquisiciones por defecto
                    'pais' => '01', // México - código para archivo TXT
                    'pais_nombre' => $proveedor->pais ?? 'MEX', // Nombre del país
                    'base_iva_16' => 0,
                    'iva_16' => 0,
                    'base_iva_8' => 0,
                    'iva_8' => 0,
                    'base_iva_0' => 0,
                    'base_exenta' => 0,
                    'iva_retenido' => 0,
                    'isr_retenido' => 0,
                    'total_pagado' => 0,
                    'facturas' => []
                ];
            }

            // Obtener datos del CFDI para determinar tasa de IVA
            $cfdi_data = $this->obtenerDatosCFDI($pago->UUID, $team_id);

            if ($cfdi_data) {
                $iva_pagado = floatval($pago->iva_pagado);
                $tasa_iva = $cfdi_data['tasa_iva'];

                // Calcular base según la tasa
                if ($tasa_iva == 0.16) {
                    $base = $iva_pagado / 0.16;
                    $proveedores_diot[$rfc]['base_iva_16'] += $base;
                    $proveedores_diot[$rfc]['iva_16'] += $iva_pagado;
                } elseif ($tasa_iva == 0.08) {
                    $base = $iva_pagado / 0.08;
                    $proveedores_diot[$rfc]['base_iva_8'] += $base;
                    $proveedores_diot[$rfc]['iva_8'] += $iva_pagado;
                } elseif ($tasa_iva == 0) {
                    // Para tasa 0%, necesitamos obtener la base del subtotal
                    $base = $cfdi_data['subtotal'];
                    $proveedores_diot[$rfc]['base_iva_0'] += $base;
                }

                $proveedores_diot[$rfc]['total_pagado'] += ($iva_pagado / max($tasa_iva, 0.16));

                // Agregar detalles de factura
                $proveedores_diot[$rfc]['facturas'][] = [
                    'uuid' => $pago->UUID,
                    'fecha_pago' => $pago->fecha_pago,
                    'poliza' => $pago->poliza,
                    'iva_pagado' => $iva_pagado,
                    'tasa' => $tasa_iva
                ];
            }
        }

        // Obtener retenciones de IVA e ISR
        $this->obtenerRetenciones($proveedores_diot, $periodo, $ejercicio, $team_id);

        return array_values($proveedores_diot);
    }

    /**
     * Obtiene datos fiscales de un CFDI
     */
    private function obtenerDatosCFDI($uuid, $team_id)
    {
        $cfdi = Almacencfdis::where('UUID', $uuid)
            ->where('team_id', $team_id)
            ->first();

        if (!$cfdi) {
            return null;
        }

        try {
            $xml = \CfdiUtils\Cfdi::newFromString($cfdi->content);
            $CFDI = $xml->getQuickReader();
            $impuestos = $CFDI->Impuestos;

            $tasa_iva = 0;
            $subtotal = floatval($CFDI['SubTotal']);

            // Obtener tasa de IVA del CFDI
            if (isset($impuestos->Traslados)) {
                foreach ($impuestos->Traslados() as $traslado) {
                    if ($traslado['impuesto'] == '002') { // IVA
                        $tasa_iva = floatval($traslado['tasaOCuota'] ?? $traslado['TasaOCuota'] ?? 0);
                        break;
                    }
                }
            }

            return [
                'tasa_iva' => $tasa_iva,
                'subtotal' => $subtotal
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene las retenciones de IVA e ISR por proveedor
     */
    private function obtenerRetenciones(&$proveedores_diot, $periodo, $ejercicio, $team_id)
    {
        // Retenciones de IVA (código 21610000 - cargo)
        $ret_iva = DB::table('auxiliares')
            ->join('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
            ->leftJoin('ingresos_egresos', 'auxiliares.igeg_id', '=', 'ingresos_egresos.id')
            ->leftJoin('almacencfdis', 'ingresos_egresos.xml_id', '=', 'almacencfdis.id')
            ->where('cat_polizas.tipo', 'Eg')
            ->where('cat_polizas.periodo', $periodo)
            ->where('cat_polizas.ejercicio', $ejercicio)
            ->where('cat_polizas.team_id', $team_id)
            ->where('auxiliares.codigo', '21610000')
            ->where('auxiliares.cargo', '>', 0)
            ->whereNotNull('almacencfdis.Emisor_Rfc')
            ->select(
                'almacencfdis.Emisor_Rfc as rfc',
                DB::raw('SUM(auxiliares.cargo) as total_ret_iva')
            )
            ->groupBy('almacencfdis.Emisor_Rfc')
            ->get();

        foreach ($ret_iva as $ret) {
            if (isset($proveedores_diot[$ret->rfc])) {
                $proveedores_diot[$ret->rfc]['iva_retenido'] = floatval($ret->total_ret_iva);
            }
        }

        // Retenciones de ISR (código 21604000 - cargo)
        $ret_isr = DB::table('auxiliares')
            ->join('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
            ->leftJoin('ingresos_egresos', 'auxiliares.igeg_id', '=', 'ingresos_egresos.id')
            ->leftJoin('almacencfdis', 'ingresos_egresos.xml_id', '=', 'almacencfdis.id')
            ->where('cat_polizas.tipo', 'Eg')
            ->where('cat_polizas.periodo', $periodo)
            ->where('cat_polizas.ejercicio', $ejercicio)
            ->where('cat_polizas.team_id', $team_id)
            ->where('auxiliares.codigo', '21604000')
            ->where('auxiliares.cargo', '>', 0)
            ->whereNotNull('almacencfdis.Emisor_Rfc')
            ->select(
                'almacencfdis.Emisor_Rfc as rfc',
                DB::raw('SUM(auxiliares.cargo) as total_ret_isr')
            )
            ->groupBy('almacencfdis.Emisor_Rfc')
            ->get();

        foreach ($ret_isr as $ret) {
            if (isset($proveedores_diot[$ret->rfc])) {
                $proveedores_diot[$ret->rfc]['isr_retenido'] = floatval($ret->total_ret_isr);
            }
        }
    }
}
