<?php

namespace App\Services;

use App\Models\CatCuentas;
use Illuminate\Support\Facades\DB;

class CatalogoCuentasXmlService
{
    public function generar(int $empresa, string $ejercicio, string $periodo)
    {
        // Obtener RFC de la empresa
        $team = DB::table('teams')->where('id', $empresa)->first();
        $rfc = $team->rfc ?? '';

        // Obtener catálogo de cuentas
        $cuentas = CatCuentas::where('team_id', $empresa)
            ->orderBy('codigo')
            ->get();

        // Crear el XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Namespace y esquema
        $catalogo = $xml->createElementNS(
            'http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/CatalogoCuentas',
            'catalogocuentas:Catalogo'
        );

        $catalogo->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/CatalogoCuentas http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/CatalogoCuentas/CatalogoCuentas_1_3.xsd'
        );

        $catalogo->setAttribute('Version', '1.3');
        $catalogo->setAttribute('RFC', $rfc);
        $catalogo->setAttribute('Mes', str_pad($periodo, 2, '0', STR_PAD_LEFT));
        $catalogo->setAttribute('Anio', $ejercicio);

        $xml->appendChild($catalogo);

        // Agregar cuentas
        $cuentasElement = $xml->createElement('catalogocuentas:Ctas');

        foreach ($cuentas as $cuenta) {
            $ctaElement = $xml->createElement('catalogocuentas:Cta');
            $ctaElement->setAttribute('CodAgrup', $this->obtenerCodigoAgrupador($cuenta->codigo));
            $ctaElement->setAttribute('NumCta', $cuenta->codigo);
            $ctaElement->setAttribute('Desc', $this->limpiarTexto($cuenta->nombre));
            $ctaElement->setAttribute('Nivel', $this->calcularNivel($cuenta->codigo));
            $ctaElement->setAttribute('Natur', $cuenta->naturaleza == 'D' ? 'D' : 'A');

            $cuentasElement->appendChild($ctaElement);
        }

        $catalogo->appendChild($cuentasElement);

        // Guardar archivo
        $nombreArchivo = $rfc . $ejercicio . str_pad($periodo, 2, '0', STR_PAD_LEFT) . 'CT.xml';
        $rutaArchivo = storage_path('app/public/contabilidad_electronica/' . $nombreArchivo);

        if (!file_exists(dirname($rutaArchivo))) {
            mkdir(dirname($rutaArchivo), 0777, true);
        }

        $xml->save($rutaArchivo);

        return $rutaArchivo;
    }

    private function obtenerCodigoAgrupador(string $cuenta): string
    {
        // Mapeo básico al código agrupador del SAT
        $primerDigito = substr($cuenta, 0, 1);

        $mapeo = [
            '1' => '100', // Activo
            '2' => '200', // Pasivo
            '3' => '300', // Capital
            '4' => '400', // Ingresos
            '5' => '500', // Costos
            '6' => '600', // Gastos
            '7' => '700', // Otros
        ];

        return $mapeo[$primerDigito] ?? '100';
    }

    private function calcularNivel(string $cuenta): int
    {
        // Calcular el nivel según la estructura de la cuenta
        $segmentos = explode('-', $cuenta);
        if (count($segmentos) > 1) {
            return count($segmentos);
        }

        // Si no tiene guiones, calcular por longitud
        $longitud = strlen(str_replace(['-', '.'], '', $cuenta));
        if ($longitud <= 1) return 1;
        if ($longitud <= 2) return 2;
        if ($longitud <= 4) return 3;
        if ($longitud <= 6) return 4;
        return 5;
    }

    private function limpiarTexto(string $texto): string
    {
        // Eliminar caracteres especiales no permitidos en XML
        $texto = strip_tags($texto);
        $texto = preg_replace('/[^\p{L}\p{N}\s\-.,]/u', '', $texto);
        return trim($texto);
    }
}
