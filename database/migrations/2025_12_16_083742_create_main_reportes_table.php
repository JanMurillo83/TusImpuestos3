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
        Schema::create('main_reportes', function (Blueprint $table) {
            $table->id();
            $table->string('reporte');
            $table->string('ruta');
            $table->string('tipo');
            $table->string('pdf');
            $table->string('xls');
            $table->timestamps();
        });
        DB::table('main_reportes')->insert([
            ['reporte'=>'Balance General','ruta'=>'BGralNew','tipo'=>'contable','pdf'=>'SI','xls'=>'SI'],
            ['reporte'=>'Balanza de Comprobación','ruta'=>'BalanzaNew','tipo'=>'contable','pdf'=>'SI','xls'=>'SI'],
            ['reporte'=>'Estado de Resultados','ruta'=>'EdoreNew','tipo'=>'contable','pdf'=>'SI','xls'=>'SI'],
            ['reporte'=>'Reporte de Auxiliares','ruta'=>'AuxiliaresPeriodo','tipo'=>'contable','pdf'=>'SI','xls'=>'SI'],
            ['reporte'=>'Pólizas Descuadradas','ruta'=>'PolizasDescuadradas','tipo'=>'contable','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'DIOT General','ruta'=>'ReporteDiotGeneral','tipo'=>'contable','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'DIOT Detalle','ruta'=>'ReporteDiotDetalle','tipo'=>'contable','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'Afectaciones de IVA','ruta'=>'ReporteAfectaciones','tipo'=>'contable','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'Reporte de Ventas','ruta'=>'ReportesAdmin.Ventas','tipo'=>'administrativo','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'Reporte de Facturas','ruta'=>'ReportesAdmin.Facturacion','tipo'=>'administrativo','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'Cuentas por Cobrar','ruta'=>'ReportesAdmin.CuentasCobrar','tipo'=>'administrativo','pdf'=>'SI','xls'=>'NO'],
            ['reporte'=>'Cuentas por Pagar','ruta'=>'ReportesAdmin.CuentasPagar','tipo'=>'administrativo','pdf'=>'SI','xls'=>'NO'],


        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_reportes');
    }
};
