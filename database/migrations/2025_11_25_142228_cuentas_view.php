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
        DB::statement(<<<SQL
                CREATE VIEW cuentas_detalle_view AS
                SELECT
                    id,
                    codigo,
                    nombre,
                    team_id
                FROM cat_cuentas
                WHERE tipo = 'D'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW cuentas_detalle_view;');
    }
};
