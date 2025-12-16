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
            $table->string('formato');
            $table->timestamps();
        });
        DB::table('main_reportes')->insert([
            ['reporte'=>'Balance General','ruta'=>'BGralNew','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'Balanza de Comprobación','ruta'=>'BalanzaNew','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'Estado de Resultados','ruta'=>'EdoreNew','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'Reporte de Auxiliares','ruta'=>'AuxiliaresPeriodo','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'Pólizas Descuadradas','ruta'=>'PolizasDescuadradas','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'DIOT General','ruta'=>'ReporteDiotGeneral','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'DIOT Detalle','ruta'=>'ReporteDiotDetalle','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'Afectaciones de IVA','ruta'=>'ReporteAfectaciones','tipo'=>'contable','formato'=>'pdf'],
            ['reporte'=>'Reporte de Ventas','ruta'=>'ReportesAdmin.Ventas','tipo'=>'administrativo','formato'=>'pdf'],
            ['reporte'=>'Reporte de Facturas','ruta'=>'ReportesAdmin.Facturacion','tipo'=>'administrativo','formato'=>'pdf'],
            ['reporte'=>'Cuentas por Cobrar','ruta'=>'ReportesAdmin.CuentasCobrar','tipo'=>'administrativo','formato'=>'pdf'],
            ['reporte'=>'Cuentas por Pagar','ruta'=>'ReportesAdmin.CuentasPagar','tipo'=>'administrativo','formato'=>'pdf'],
            ['reporte'=>'Balance General','ruta'=>'BGralNew','tipo'=>'contable','formato'=>'xls'],
            ['reporte'=>'Balanza de Comprobación','ruta'=>'BalanzaNew','tipo'=>'contable','formato'=>'xls'],
            ['reporte'=>'Estado de Resultados','ruta'=>'EdoreNew','tipo'=>'contable','formato'=>'xls'],
            ['reporte'=>'Reporte de Auxiliares','ruta'=>'AuxiliaresPeriodo','tipo'=>'contable','formato'=>'xls'],


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
