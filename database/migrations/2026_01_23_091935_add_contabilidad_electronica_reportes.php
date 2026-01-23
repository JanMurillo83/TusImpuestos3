<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('main_reportes')->insert([
            ['reporte'=>'Catálogo de Cuentas (XML)','ruta'=>'CatalogoCuentas_XML','ruta_excel'=>'','tipo'=>'contable','pdf'=>'NO','xls'=>'NO'],
            ['reporte'=>'Balanza de Comprobación (XML)','ruta'=>'BalanzaComprobacion_XML','ruta_excel'=>'','tipo'=>'contable','pdf'=>'NO','xls'=>'NO'],
            ['reporte'=>'Pólizas del Periodo (XML)','ruta'=>'PolizasPeriodo_XML','ruta_excel'=>'','tipo'=>'contable','pdf'=>'NO','xls'=>'NO'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('main_reportes')->whereIn('ruta', [
            'CatalogoCuentas_XML',
            'BalanzaComprobacion_XML',
            'PolizasPeriodo_XML'
        ])->delete();
    }
};
