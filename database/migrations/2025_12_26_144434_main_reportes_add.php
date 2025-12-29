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
        Schema::table('main_reportes', function (Blueprint $table) {
            $table->string('ruta_excel')->after('ruta')->default('');
        });
        DB::statement("UPDATE main_reportes SET ruta_excel = 'BGralNew_Excel' WHERE id = 1");
        DB::statement("UPDATE main_reportes SET ruta_excel = 'BalanzaNew_Excel' WHERE id = 2");
        DB::statement("UPDATE main_reportes SET ruta_excel = 'EdoreNew_Excel' WHERE id = 3");
        DB::statement("UPDATE main_reportes SET ruta_excel = 'AuxiliaresPeriodo_Excel' WHERE id = 4");
        DB::statement("UPDATE main_reportes SET ruta_excel = ruta WHERE id > 4");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
