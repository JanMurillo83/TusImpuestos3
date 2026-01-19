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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('calle', 255)->nullable()->after('direccion');
            $table->string('no_exterior', 50)->nullable()->after('calle');
            $table->string('no_interior', 50)->nullable()->after('no_exterior');
            $table->string('colonia', 255)->nullable()->after('no_interior');
            $table->string('municipio', 255)->nullable()->after('colonia');
            $table->string('estado', 255)->nullable()->after('municipio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['calle', 'no_exterior', 'no_interior', 'colonia', 'municipio', 'estado']);
        });
    }
};
