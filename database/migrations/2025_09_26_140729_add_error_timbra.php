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
        //\Illuminate\Support\Facades\DB::statement("UPDATE facturas SET pendiente_pago = total WHERE id > 0");
        Schema::table('facturas', function (Blueprint $table) {
            $table->text('error_timbrado')->nullable();
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
