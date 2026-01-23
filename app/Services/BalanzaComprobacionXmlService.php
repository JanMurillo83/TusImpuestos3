<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BalanzaComprobacionXmlService
{
    public function generar(int $empresa, string $ejercicio, string $periodo)
    {
        // Obtener RFC de la empresa
        $team = DB::table('teams')->where('id', $empresa)->first();
        $rfc = $team->rfc ?? '';

        // Obtener balanza de comprobación
        $balanza = DB::table('reportes')
            ->where('team_id', $empresa)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->orderBy('cuenta')
            ->get();

        // Crear el XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Namespace y esquema
        $balanzaElement = $xml->createElementNS(
            'http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/BalanzaComprobacion',
            'BCE:Balanza'
        );

        $balanzaElement->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/BalanzaComprobacion http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/BalanzaComprobacion/BalanzaComprobacion_1_3.xsd'
        );

        $balanzaElement->setAttribute('Version', '1.3');
        $balanzaElement->setAttribute('RFC', $rfc);
        $balanzaElement->setAttribute('Mes', str_pad($periodo, 2, '0', STR_PAD_LEFT));
        $balanzaElement->setAttribute('Anio', $ejercicio);
        $balanzaElement->setAttribute('TipoEnvio', 'N'); // N=Normal, C=Complementaria

        $xml->appendChild($balanzaElement);

        // Calcular totales
        $totalDebeInicial = 0;
        $totalHaberInicial = 0;
        $totalDebe = 0;
        $totalHaber = 0;
        $totalDebeFinal = 0;
        $totalHaberFinal = 0;

        foreach ($balanza as $cuenta) {
            $totalDebeInicial += floatval($cuenta->saldo_ini_d ?? 0);
            $totalHaberInicial += floatval($cuenta->saldo_ini_a ?? 0);
            $totalDebe += floatval($cuenta->cargos ?? 0);
            $totalHaber += floatval($cuenta->abonos ?? 0);
            $totalDebeFinal += floatval($cuenta->saldo_fin_d ?? 0);
            $totalHaberFinal += floatval($cuenta->saldo_fin_a ?? 0);
        }

        // Agregar cuentas
        $cuentasElement = $xml->createElement('BCE:Ctas');

        foreach ($balanza as $cuenta) {
            $ctaElement = $xml->createElement('BCE:Cta');
            $ctaElement->setAttribute('NumCta', $cuenta->cuenta);
            $ctaElement->setAttribute('SaldoInicial', number_format($cuenta->saldo_inicial ?? 0, 2, '.', ''));
            $ctaElement->setAttribute('Debe', number_format($cuenta->cargos ?? 0, 2, '.', ''));
            $ctaElement->setAttribute('Haber', number_format($cuenta->abonos ?? 0, 2, '.', ''));
            $ctaElement->setAttribute('SaldoFinal', number_format($cuenta->saldo_final ?? 0, 2, '.', ''));

            $cuentasElement->appendChild($ctaElement);
        }

        $balanzaElement->appendChild($cuentasElement);

        // Guardar archivo
        $nombreArchivo = $rfc . $ejercicio . str_pad($periodo, 2, '0', STR_PAD_LEFT) . 'BN.xml';
        $rutaArchivo = storage_path('app/public/contabilidad_electronica/' . $nombreArchivo);

        if (!file_exists(dirname($rutaArchivo))) {
            mkdir(dirname($rutaArchivo), 0777, true);
        }

        $xml->save($rutaArchivo);

        return $rutaArchivo;
    }
}
