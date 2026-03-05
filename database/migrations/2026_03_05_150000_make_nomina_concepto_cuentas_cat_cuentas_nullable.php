<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('nomina_concepto_cuentas')) {
            return;
        }

        DB::statement('ALTER TABLE nomina_concepto_cuentas MODIFY cat_cuentas_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('nomina_concepto_cuentas')) {
            return;
        }

        DB::statement('ALTER TABLE nomina_concepto_cuentas MODIFY cat_cuentas_id BIGINT UNSIGNED NOT NULL');
    }
};
