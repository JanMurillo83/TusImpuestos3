<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movbancos', function($table) {
            $table->decimal('tcambio',18,8)->default(1.00);
            $table->decimal('pendiente_apli',18,8)->default(0.00);
        });
        DB::statement("UPDATE movbancos SET pendiente_apli = importe WHERE id > 0");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
