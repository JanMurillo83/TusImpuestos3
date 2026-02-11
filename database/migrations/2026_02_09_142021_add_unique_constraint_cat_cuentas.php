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
        // Agregar índice único compuesto para evitar duplicados de codigo + team_id
        Schema::table('cat_cuentas', function (Blueprint $table) {
            //$table->unique(['codigo', 'team_id'], 'unique_codigo_team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cat_cuentas', function (Blueprint $table) {
            //$table->dropUnique('unique_codigo_team');
        });
    }
};
