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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('tipo_tercero', 2)->default('04')->after('cuenta_contable')->comment('04=Nacional, 15=Extranjero');
            $table->string('tipo_operacion', 2)->default('85')->after('tipo_tercero')->comment('03=Servicios, 06=Arrendamiento, 85=Otros');
            $table->string('pais', 3)->default('MEX')->after('tipo_operacion')->nullable()->comment('País del proveedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['tipo_tercero', 'tipo_operacion', 'pais']);
        });
    }
};
