<?php

use App\Models\SeriesFacturas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('series_facturas') || ! Schema::hasTable('teams')) {
            return;
        }

        $seriesConfig = [
            SeriesFacturas::TIPO_COTIZACIONES => [
                'serie' => 'C',
                'descripcion' => 'Cotizaciones',
                'table' => 'cotizaciones',
            ],
            SeriesFacturas::TIPO_REMISIONES => [
                'serie' => 'R',
                'descripcion' => 'Remisiones',
                'table' => 'remisiones',
            ],
            SeriesFacturas::TIPO_REQUISICIONES => [
                'serie' => 'RQ',
                'descripcion' => 'Requisiciones de Compra',
                'table' => 'requisiciones',
            ],
            SeriesFacturas::TIPO_ORDENES_COMPRA => [
                'serie' => 'OC',
                'descripcion' => 'Ordenes de Compra',
                'table' => 'ordenes',
            ],
            SeriesFacturas::TIPO_ORDENES_INSUMOS => [
                'serie' => 'OI',
                'descripcion' => 'Ordenes de Compra Insumos',
                'table' => 'ordenes_insumos',
            ],
            SeriesFacturas::TIPO_COMPRAS => [
                'serie' => 'E',
                'descripcion' => 'Entradas (Compras)',
                'table' => 'compras',
            ],
        ];

        $teamIds = DB::table('teams')->pluck('id');

        foreach ($teamIds as $teamId) {
            foreach ($seriesConfig as $tipo => $config) {
                $exists = DB::table('series_facturas')
                    ->where('team_id', $teamId)
                    ->where('tipo', $tipo)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $folio = 0;
                if (Schema::hasTable($config['table'])) {
                    $folio = (int) (DB::table($config['table'])
                        ->where('team_id', $teamId)
                        ->max('folio') ?? 0);
                }

                DB::table('series_facturas')->insert([
                    'serie' => $config['serie'],
                    'tipo' => $tipo,
                    'folio' => $folio,
                    'descripcion' => $config['descripcion'],
                    'team_id' => $teamId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No rollback to avoid removing user-managed series.
    }
};
