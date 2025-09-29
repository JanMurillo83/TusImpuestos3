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
        //DB::statement('ALTER TABLE facturas DROP COLUMN pendiente_pago');
        Schema::table('facturas', function (Blueprint $table) {
            $table->decimal('pendiente_pago',18,8)->after('xml_cancela')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
