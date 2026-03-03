<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'compras_cotizaciones'],
            ['description' => 'Acceso exclusivo a Compras y Cotizaciones']
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'compras_cotizaciones')->delete();
    }
};
