<?php

namespace App\Services;

use App\Models\Almacencfdis;
use App\Models\Xmlfiles;
use App\Support\CfdiPagosHelper;
use CfdiUtils\Cfdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para el procesamiento de archivos XML de CFDIs
 *
 * Este servicio se encarga de:
 * - Extraer información de archivos XML
 * - Guardar CFDIs en la base de datos
 * - Procesar lotes de archivos
 * - Manejo de duplicados
 */
class XmlProcessorService
{
    /**
     * Procesa un directorio completo de archivos XML
     */
    public function processDirectory(string $directory, int $teamId, string $xmlType): array
    {
        $results = [
            'success' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_messages' => []
        ];

        if (!is_dir($directory)) {
            Log::warning('Directorio no encontrado para procesar XMLs', [
                'directory' => $directory,
                'team_id' => $teamId
            ]);
            return $results;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $filename) {
            $filePath = $directory . $filename;

            if (!is_file($filePath) || pathinfo($filePath, PATHINFO_EXTENSION) !== 'xml') {
                continue;
            }

            $result = $this->processXmlFile($filePath, $teamId, $xmlType);

            if ($result['success']) {
                $results['success']++;
            } elseif ($result['skipped']) {
                $results['skipped']++;
            } else {
                $results['errors']++;
                $results['error_messages'][] = [
                    'file' => $filename,
                    'error' => $result['error']
                ];
            }
        }

        Log::info('Procesamiento de directorio completado', [
            'directory' => $directory,
            'team_id' => $teamId,
            'xml_type' => $xmlType,
            'results' => $results
        ]);

        return $results;
    }

    /**
     * Procesa un archivo XML individual
     */
    public function processXmlFile(string $filePath, int $teamId, string $xmlType): array
    {
        try {
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'skipped' => false,
                    'error' => 'Archivo no encontrado'
                ];
            }

            $xmlContents = file_get_contents($filePath);
            if ($xmlContents === false) {
                return [
                    'success' => false,
                    'skipped' => false,
                    'error' => 'No se pudo leer el archivo'
                ];
            }

            $cfdiData = $this->extractCfdiData($xmlContents, $filePath, $teamId, $xmlType);

            if (!$cfdiData['success']) {
                return [
                    'success' => false,
                    'skipped' => false,
                    'error' => $cfdiData['error']
                ];
            }

            // Verificar si ya existe
            $exists = $this->checkIfExists($cfdiData['uuid'], $teamId, $xmlType);

            if ($exists) {
                return [
                    'success' => false,
                    'skipped' => true,
                    'error' => 'UUID ya existe en el sistema'
                ];
            }

            // Guardar en base de datos
            $this->saveCfdi($cfdiData);

            return ['success' => true, 'skipped' => false, 'uuid' => $cfdiData['uuid']];

        } catch (\Exception $e) {
            Log::error('Error procesando archivo XML', [
                'file' => $filePath,
                'team_id' => $teamId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'skipped' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extrae los datos del CFDI desde el XML
     */
    private function extractCfdiData(string $xmlContents, string $filePath, int $teamId, string $xmlType): array
    {
        try {
            $cfdi = Cfdi::newFromString($xmlContents);
            $comprobante = $cfdi->getNode();

            $emisor = $comprobante->searchNode('cfdi:Emisor');
            $receptor = $comprobante->searchNode('cfdi:Receptor');
            $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');

            if (!$tfd || !isset($tfd['UUID'])) {
                return [
                    'success' => false,
                    'error' => 'No se encontró el UUID en el TimbreFiscalDigital'
                ];
            }

            $pagoscom = CfdiPagosHelper::findPagosComplement($comprobante);
            $impuestos = $comprobante->searchNode('cfdi:Impuestos');
            $tipocom = $comprobante['TipoDeComprobante'];

            // Calcular importes según el tipo de comprobante
            $importes = $this->calculateImportes($comprobante, $impuestos, $pagoscom, $tipocom);

            // Extraer fecha y periodo
            $fech = $comprobante['Fecha'];
            [$fechacom, $horacom] = explode('T', $fech);
            [$aniocom, $mescom, $diacom] = explode('-', $fechacom);

            return [
                'success' => true,
                'uuid' => $tfd['UUID'],
                'serie' => $comprobante['Serie'] ?? '',
                'folio' => $comprobante['Folio'] ?? '',
                'version' => $comprobante['Version'] ?? '',
                'fecha' => $comprobante['Fecha'],
                'moneda' => $comprobante['Moneda'] ?? 'MXN',
                'tipo_de_comprobante' => $comprobante['TipoDeComprobante'],
                'metodo_pago' => $comprobante['MetodoPago'] ?? '',
                'forma_pago' => $comprobante['FormaPago'] ?? '',
                'emisor_rfc' => $emisor['Rfc'] ?? '',
                'emisor_nombre' => $emisor['Nombre'] ?? '',
                'emisor_regimen_fiscal' => $emisor['RegimenFiscal'] ?? '',
                'receptor_rfc' => $receptor['Rfc'] ?? '',
                'receptor_nombre' => $receptor['Nombre'] ?? '',
                'receptor_regimen_fiscal' => $receptor['RegimenFiscal'] ?? '',
                'subtotal' => $importes['subtotal'],
                'descuento' => $importes['descuento'],
                'total' => $importes['total'],
                'tipo_cambio' => $importes['tipocambio'],
                'total_impuestos_trasladados' => $importes['traslado'],
                'total_impuestos_retenidos' => $importes['retencion'],
                'content' => $xmlContents,
                'user_tax' => $xmlType === 'Emitidos' ? ($emisor['Rfc'] ?? '') : ($emisor['Rfc'] ?? ''),
                'used' => 'NO',
                'xml_type' => $xmlType,
                'periodo' => intval($mescom),
                'ejercicio' => intval($aniocom),
                'team_id' => $teamId,
                'archivoxml' => $filePath
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error extrayendo datos del XML: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calcula los importes del CFDI según el tipo de comprobante
     */
    private function calculateImportes($comprobante, $impuestos, $pagoscom, string $tipocom): array
    {
        $result = [
            'subtotal' => 0.0,
            'descuento' => 0.0,
            'traslado' => 0.0,
            'retencion' => 0.0,
            'total' => 0.0,
            'tipocambio' => 1.0
        ];

        if ($tipocom !== 'P') {
            // Comprobante normal (no es complemento de pago)
            $result['subtotal'] = floatval($comprobante['SubTotal'] ?? 0);
            $result['descuento'] = floatval($comprobante['Descuento'] ?? 0);
            $result['total'] = floatval($comprobante['Total'] ?? 0);
            $result['tipocambio'] = floatval($comprobante['TipoCambio'] ?? 1);

            if ($impuestos) {
                $result['traslado'] = floatval($impuestos['TotalImpuestosTrasladados'] ?? 0);
                $result['retencion'] = floatval($impuestos['TotalImpuestosRetenidos'] ?? 0);
            }
        } else {
            // Complemento de pago
            $pagostot = CfdiPagosHelper::calculatePagosTotales($pagoscom);
            $result['subtotal'] = $pagostot['subtotal'];
            $result['traslado'] = $pagostot['traslado'];
            $result['total'] = $pagostot['total'];
            $result['tipocambio'] = $pagostot['tipocambio'];
        }

        return $result;
    }

    /**
     * Verifica si el UUID ya existe en el sistema
     */
    private function checkIfExists(string $uuid, int $teamId, string $xmlType): bool
    {
        $uuidUpper = strtoupper($uuid);

        return DB::table('almacencfdis')
            ->where(DB::raw('UPPER(UUID)'), $uuidUpper)
            ->where('team_id', $teamId)
            ->where('xml_type', $xmlType)
            ->exists();
    }

    /**
     * Guarda el CFDI en la base de datos
     */
    private function saveCfdi(array $cfdiData): void
    {
        DB::transaction(function () use ($cfdiData) {
            // Guardar en almacencfdis
            Almacencfdis::create([
                'Serie' => $cfdiData['serie'],
                'Folio' => $cfdiData['folio'],
                'Version' => $cfdiData['version'],
                'Fecha' => $cfdiData['fecha'],
                'Moneda' => $cfdiData['moneda'],
                'TipoDeComprobante' => $cfdiData['tipo_de_comprobante'],
                'MetodoPago' => $cfdiData['metodo_pago'],
                'FormaPago' => $cfdiData['forma_pago'],
                'Emisor_Rfc' => $cfdiData['emisor_rfc'],
                'Emisor_Nombre' => $cfdiData['emisor_nombre'],
                'Emisor_RegimenFiscal' => $cfdiData['emisor_regimen_fiscal'],
                'Receptor_Rfc' => $cfdiData['receptor_rfc'],
                'Receptor_Nombre' => $cfdiData['receptor_nombre'],
                'Receptor_RegimenFiscal' => $cfdiData['receptor_regimen_fiscal'],
                'UUID' => $cfdiData['uuid'],
                'Total' => $cfdiData['total'],
                'SubTotal' => $cfdiData['subtotal'],
                'Descuento' => $cfdiData['descuento'],
                'TipoCambio' => $cfdiData['tipo_cambio'],
                'TotalImpuestosTrasladados' => $cfdiData['total_impuestos_trasladados'],
                'TotalImpuestosRetenidos' => $cfdiData['total_impuestos_retenidos'],
                'content' => $cfdiData['content'],
                'user_tax' => $cfdiData['user_tax'],
                'used' => $cfdiData['used'],
                'xml_type' => $cfdiData['xml_type'],
                'periodo' => $cfdiData['periodo'],
                'ejercicio' => $cfdiData['ejercicio'],
                'team_id' => $cfdiData['team_id'],
                'archivoxml' => $cfdiData['archivoxml']
            ]);

            // Guardar en xmlfiles
            Xmlfiles::create([
                'taxid' => $cfdiData['user_tax'],
                'uuid' => $cfdiData['uuid'],
                'content' => $cfdiData['content'],
                'periodo' => str_pad($cfdiData['periodo'], 2, '0', STR_PAD_LEFT),
                'ejercicio' => $cfdiData['ejercicio'],
                'tipo' => $cfdiData['xml_type'],
                'solicitud' => 'Importacion',
                'team_id' => $cfdiData['team_id']
            ]);
        });

        Log::info('CFDI guardado correctamente', [
            'uuid' => $cfdiData['uuid'],
            'team_id' => $cfdiData['team_id'],
            'xml_type' => $cfdiData['xml_type']
        ]);
    }

    /**
     * Procesa archivos PDF y los vincula con los CFDIs
     */
    public function processPdfDirectory(string $directory, int $teamId): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'error_messages' => []
        ];

        if (!is_dir($directory)) {
            Log::warning('Directorio PDF no encontrado', [
                'directory' => $directory,
                'team_id' => $teamId
            ]);
            return $results;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $filename) {
            $filePath = $directory . $filename;

            if (!is_file($filePath) || pathinfo($filePath, PATHINFO_EXTENSION) !== 'pdf') {
                continue;
            }

            try {
                $fileInfo = pathinfo($filePath);
                $uuid = strtoupper($fileInfo['filename']);

                $updated = DB::table('almacencfdis')
                    ->where(DB::raw('UPPER(UUID)'), $uuid)
                    ->where('team_id', $teamId)
                    ->update(['archivopdf' => $filePath]);

                if ($updated > 0) {
                    $results['success']++;
                } else {
                    $results['errors']++;
                    $results['error_messages'][] = [
                        'file' => $filename,
                        'error' => 'No se encontró CFDI con ese UUID'
                    ];
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_messages'][] = [
                    'file' => $filename,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Procesamiento de PDFs completado', [
            'directory' => $directory,
            'team_id' => $teamId,
            'results' => $results
        ]);

        return $results;
    }
}
