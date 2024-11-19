<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS `busca_sat`');
        DB::statement('DROP VIEW IF EXISTS `PolCuentas`');
        DB::statement('CREATE VIEW `busca_sat` AS SELECT `cvesats`.`clave` AS `clave`,
        CONCAT(`cvesats`.`clave`,\'-\', `cvesats`.`descripcion`) AS `nombre`
        FROM `cvesats`');
        DB::statement('CREATE VIEW `PolCuentas` AS SELECT `cat_cuentas`.`codigo`
        AS `codigo`, CONCAT(`cat_cuentas`.`codigo`, \'-\',`cat_cuentas`.`nombre`)
        AS `mostrar`, `cat_cuentas`.`team_id` AS `team_id` FROM `cat_cuentas`
        WHERE (`cat_cuentas`.`tipo` = \'D\')');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
