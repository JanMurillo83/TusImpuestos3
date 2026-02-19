<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TempCfdis;
use App\Services\SatDescargaMasivaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TempCfdisController extends Controller
{
    /**
     * Consulta metadatos de CFDIs del SAT usando Descarga Masiva y llena temp_cfdis
     *
     * Usa el Web Service SOAP oficial del SAT (más estable que el scraper web)
     * con RequestType::metadata() para obtener CSV con información de los CFDIs
     *
     * @param int $teamId
     * @param string $fechaInicial formato Y-m-d
     * @param string $fechaFinal formato Y-m-d
     * @return array ['success' => bool, 'emitidos' => int, 'recibidos' => int, 'total' => int, 'error' => string|null, 'fase' => string]
     */
    public function consultarMetadatos(int $teamId, string $fechaInicial, string $fechaFinal): array
    {
        $record = Team::findOrFail($teamId);

        // Limpiar registros previos
        TempCfdis::where('team_id', $teamId)->delete();

        // Inicializar servicio de descarga masiva
        $masivaService = new SatDescargaMasivaService($record);

        $init = $masivaService->initializeService();
        if (!$init['valid']) {
            return [
                'success' => false,
                'emitidos' => 0,
                'recibidos' => 0,
                'total' => 0,
                'error' => $init['error'],
                'fase' => 'inicialización',
            ];
        }

        // Consultar emitidos
        $emitidosResult = $masivaService->consultarMetadatos($fechaInicial, $fechaFinal, 'emitidos');
        if (!$emitidosResult['success']) {
            return [
                'success' => false,
                'emitidos' => 0,
                'recibidos' => 0,
                'total' => 0,
                'error' => $emitidosResult['error'],
                'fase' => 'consulta emitidos',
            ];
        }

        // Consultar recibidos
        $recibidosResult = $masivaService->consultarMetadatos($fechaInicial, $fechaFinal, 'recibidos');
        if (!$recibidosResult['success']) {
            return [
                'success' => false,
                'emitidos' => $emitidosResult['count'],
                'recibidos' => 0,
                'total' => 0,
                'error' => $recibidosResult['error'],
                'fase' => 'consulta recibidos',
            ];
        }

        // Preparar datos para inserción
        $allData = [];

        foreach ($emitidosResult['metadata'] as $item) {
            $allData[] = [
                'UUID' => $item->uuid,
                'RfcEmisor' => $item->rfcEmisor,
                'NombreEmisor' => $item->nombreEmisor,
                'RfcReceptor' => $item->rfcReceptor,
                'NombreReceptor' => $item->nombreReceptor,
                'RfcPac' => $item->rfcPac,
                'FechaEmision' => $item->fechaEmision,
                'FechaCertificacionSat' => $item->fechaCertificacionSat,
                'Monto' => floatval(str_replace([',', '$'], ['', ''], $item->monto)),
                'EfectoComprobante' => $item->efectoComprobante,
                'Estatus' => $item->estatus,
                'FechaCancelacion' => $item->fechaCancelacion ?: null,
                'Tipo' => 'Emitidos',
                'team_id' => $teamId,
            ];
        }

        foreach ($recibidosResult['metadata'] as $item) {
            $allData[] = [
                'UUID' => $item->uuid,
                'RfcEmisor' => $item->rfcEmisor,
                'NombreEmisor' => $item->nombreEmisor,
                'RfcReceptor' => $item->rfcReceptor,
                'NombreReceptor' => $item->nombreReceptor,
                'RfcPac' => $item->rfcPac,
                'FechaEmision' => $item->fechaEmision,
                'FechaCertificacionSat' => $item->fechaCertificacionSat,
                'Monto' => floatval(str_replace([',', '$'], ['', ''], $item->monto)),
                'EfectoComprobante' => $item->efectoComprobante,
                'Estatus' => $item->estatus,
                'FechaCancelacion' => $item->fechaCancelacion ?: null,
                'Tipo' => 'Recibidos',
                'team_id' => $teamId,
            ];
        }

        // Insertar en chunks de 100
        $inserted = 0;
        foreach (array_chunk($allData, 100) as $chunk) {
            TempCfdis::insert($chunk);
            $inserted += count($chunk);
        }

        Log::info('Consulta de metadatos SAT completada (descarga masiva)', [
            'team_id' => $teamId,
            'fecha_inicial' => $fechaInicial,
            'fecha_final' => $fechaFinal,
            'emitidos' => $emitidosResult['count'],
            'recibidos' => $recibidosResult['count'],
            'total_insertados' => $inserted,
        ]);

        return [
            'success' => true,
            'emitidos' => $emitidosResult['count'],
            'recibidos' => $recibidosResult['count'],
            'total' => $inserted,
            'error' => null,
            'fase' => 'completado',
        ];
    }
}
