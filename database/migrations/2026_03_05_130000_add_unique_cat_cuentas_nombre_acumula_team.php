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
        Schema::table('cat_cuentas', function (Blueprint $table) {
            $table->unique(['team_id', 'nombre', 'acumula'], 'cat_cuentas_team_nombre_acumula_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cat_cuentas', function (Blueprint $table) {
            $table->dropUnique('cat_cuentas_team_nombre_acumula_unique');
        });
    }
};
