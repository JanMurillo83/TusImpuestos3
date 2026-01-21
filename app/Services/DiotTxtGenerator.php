<?php

namespace App\Services;

class DiotTxtGenerator
{
    /**
     * Genera el archivo TXT de DIOT en formato del SAT
     *
     * @param array $datos Datos de proveedores
     * @param int $periodo Mes (1-12)
     * @param int $ejercicio Año
     * @return string Ruta del archivo generado
     */
    public function generar($datos, $periodo, $ejercicio, $team_id)
    {
        $lineas = [];

        foreach ($datos as $proveedor) {
            $linea = $this->generarLinea($proveedor);
            $lineas[] = $linea;
        }

        // Crear archivo
        $nombreArchivo = 'DIOT_' . $team_id . '_' . $ejercicio . str_pad($periodo, 2, '0', STR_PAD_LEFT) . '.txt';
        $ruta = public_path('DIOT/' . $nombreArchivo);

        // Crear directorio si no existe
        if (!file_exists(public_path('DIOT'))) {
            mkdir(public_path('DIOT'), 0755, true);
        }

        // Escribir archivo
        file_put_contents($ruta, implode("\n", $lineas));

        return $ruta;
    }

    /**
     * Genera una línea del archivo DIOT (44 campos separados por pipe)
     *
     * Estructura según SAT:
     * 1. Tipo de tercero (04=Nacional, 15=Extranjero)
     * 2. Tipo de operación (03=Servicios, 06=Arrendamiento, 85=Otros)
     * 3. RFC
     * 4. Número de identificación fiscal (extranjeros)
     * 5. Nombre
     * 6. País de residencia
     * 7. Nacionalidad
     * 8-13. Valor actos pagados 15%
     * 14-19. Valor actos pagados 15% no acreditable
     * 20-25. Valor actos pagados 10%
     * 26-31. Valor actos pagados 10% no acreditable
     * 32-37. Valor actos pagados 16% (Base|IVA|IVA no acred|Ret IVA|Ret ISR|...)
     * 38-43. Otros campos
     * 44. País
     */
    private function generarLinea($proveedor)
    {
        $campos = [];

        // Campo 1: Tipo de tercero
        $campos[] = $proveedor['tipo_tercero']; // 04=Nacional

        // Campo 2: Tipo de operación
        $campos[] = $proveedor['tipo_operacion']; // 85=Otros

        // Campo 3: RFC
        $campos[] = $proveedor['rfc'];

        // Campos 4-5: Identificación fiscal (vacío para nacionales)
        $campos[] = '';
        $campos[] = '';

        // Campos 6-7: País y Nacionalidad (vacío para nacionales)
        $campos[] = '';
        $campos[] = '';

        // Campos 8-13: Valor actos pagados 15% (ya no aplica)
        for ($i = 0; $i < 6; $i++) {
            $campos[] = '';
        }

        // Campos 14-19: Valor actos pagados 15% no acreditable (ya no aplica)
        for ($i = 0; $i < 6; $i++) {
            $campos[] = '';
        }

        // Campos 20-25: Valor actos pagados 10% (ya no aplica)
        for ($i = 0; $i < 6; $i++) {
            $campos[] = '';
        }

        // Campos 26-31: Valor actos pagados 10% no acreditable (ya no aplica)
        for ($i = 0; $i < 6; $i++) {
            $campos[] = '';
        }

        // Campos 32-37: Valor actos pagados 16%
        // 32: Base gravada 16%
        $campos[] = $this->formatearMonto($proveedor['base_iva_16']);
        // 33-34: Vacío
        $campos[] = '';
        $campos[] = '';
        // 35: IVA pagado 16%
        $campos[] = $this->formatearMonto($proveedor['iva_16']);
        // 36: IVA retenido
        $campos[] = $this->formatearMonto($proveedor['iva_retenido']);
        // 37: ISR retenido (vacío por ahora, puede agregarse)
        $campos[] = '';

        // Campos 38-39: Importación gravada 16% (vacío)
        $campos[] = '';
        $campos[] = '';

        // Campos 40-41: Base IVA 0% e Importación 0%
        $campos[] = $this->formatearMonto($proveedor['base_iva_0']);
        $campos[] = '';

        // Campo 42: Base exenta
        $campos[] = $this->formatearMonto($proveedor['base_exenta']);

        // Campo 43: Base IVA 8%
        $campos[] = $this->formatearMonto($proveedor['base_iva_8']);

        // Campo 44: País (01 = México)
        $campos[] = $proveedor['pais'];

        return implode('|', $campos);
    }

    /**
     * Formatea un monto para el archivo DIOT (sin decimales, sin comas)
     */
    private function formatearMonto($monto)
    {
        if ($monto == 0) {
            return '';
        }
        return number_format($monto, 0, '', '');
    }
}
