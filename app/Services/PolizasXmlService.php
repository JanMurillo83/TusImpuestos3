<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use ZipArchive;

class PolizasXmlService
{
    public function generar(int $empresa, string $ejercicio, string $periodo)
    {
        // Obtener RFC de la empresa
        $team = DB::table('teams')->where('id', $empresa)->first();
        $rfc = $team->rfc ?? '';

        // Obtener pólizas del periodo
        $polizas = DB::table('cat_polizas')
            ->where('team_id', $empresa)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->orderBy('tipo')
            ->orderBy('folio')
            ->get();

        if ($polizas->isEmpty()) {
            throw new \Exception('No hay pólizas en el periodo seleccionado');
        }

        // Crear directorio temporal para los XMLs
        $dirTemp = storage_path('app/public/contabilidad_electronica/temp_polizas_' . time());
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }

        $archivosGenerados = [];

        foreach ($polizas as $poliza) {
            $archivoPoliza = $this->generarPolizaXml($poliza, $empresa, $rfc, $ejercicio, $periodo, $dirTemp);
            $archivosGenerados[] = $archivoPoliza;
        }

        // Crear archivo ZIP con todas las pólizas
        $nombreZip = $rfc . $ejercicio . str_pad($periodo, 2, '0', STR_PAD_LEFT) . 'PL.zip';
        $rutaZip = storage_path('app/public/contabilidad_electronica/' . $nombreZip);

        $zip = new ZipArchive();
        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($archivosGenerados as $archivo) {
                $zip->addFile($archivo, basename($archivo));
            }
            $zip->close();
        }

        // Limpiar archivos temporales
        foreach ($archivosGenerados as $archivo) {
            if (file_exists($archivo)) {
                unlink($archivo);
            }
        }
        if (file_exists($dirTemp)) {
            rmdir($dirTemp);
        }

        return $rutaZip;
    }

    private function generarPolizaXml($poliza, int $empresa, string $rfc, string $ejercicio, string $periodo, string $dirTemp)
    {
        // Obtener movimientos de la póliza
        $movimientos = DB::table('auxiliares')
            ->where('cat_polizas_id', $poliza->id)
            ->orderBy('nopartida')
            ->get();

        // Crear el XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Namespace y esquema
        $polizaElement = $xml->createElementNS(
            'http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/PolizasPeriodo',
            'PLZ:Polizas'
        );

        $polizaElement->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/PolizasPeriodo http://www.sat.gob.mx/esquemas/ContabilidadE/1_3/PolizasPeriodo/PolizasPeriodo_1_3.xsd'
        );

        $polizaElement->setAttribute('Version', '1.3');
        $polizaElement->setAttribute('RFC', $rfc);
        $polizaElement->setAttribute('Mes', str_pad($periodo, 2, '0', STR_PAD_LEFT));
        $polizaElement->setAttribute('Anio', $ejercicio);
        $polizaElement->setAttribute('TipoSolicitud', 'AF'); // AF=Acto de Fiscalización, FC=Fiscalización Compulsa, DE=Devolución, CO=Compensación

        $xml->appendChild($polizaElement);

        // Agregar póliza
        $poliza_elem = $xml->createElement('PLZ:Poliza');
        $poliza_elem->setAttribute('NumUnIdenPol', $poliza->folio);
        $poliza_elem->setAttribute('Fecha', date('Y-m-d', strtotime($poliza->fecha)));
        $poliza_elem->setAttribute('Concepto', $this->limpiarTexto($poliza->concepto));

        // Agregar transacciones
        foreach ($movimientos as $movimiento) {
            $transaccion = $xml->createElement('PLZ:Transaccion');
            $transaccion->setAttribute('NumCta', $movimiento->codigo ?? '');
            $transaccion->setAttribute('DesCta', $this->limpiarTexto($movimiento->cuenta ?? ''));
            $transaccion->setAttribute('Concepto', $this->limpiarTexto($movimiento->concepto ?? ''));
            $transaccion->setAttribute('Debe', number_format($movimiento->cargo ?? 0, 2, '.', ''));
            $transaccion->setAttribute('Haber', number_format($movimiento->abono ?? 0, 2, '.', ''));

            $poliza_elem->appendChild($transaccion);
        }

        $polizaElement->appendChild($poliza_elem);

        // Guardar archivo
        $tipoPoliza = strtoupper($poliza->tipo);
        $folioFormateado = str_pad($poliza->folio, 6, '0', STR_PAD_LEFT);
        $nombreArchivo = $rfc . $ejercicio . str_pad($periodo, 2, '0', STR_PAD_LEFT) . $tipoPoliza . $folioFormateado . '.xml';
        $rutaArchivo = $dirTemp . '/' . $nombreArchivo;

        $xml->save($rutaArchivo);

        return $rutaArchivo;
    }

    private function limpiarTexto(string $texto): string
    {
        // Eliminar caracteres especiales no permitidos en XML
        $texto = strip_tags($texto);
        $texto = preg_replace('/[^\p{L}\p{N}\s\-.,]/u', '', $texto);
        return trim(substr($texto, 0, 200)); // Máximo 200 caracteres según SAT
    }
}
