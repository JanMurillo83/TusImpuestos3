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
        //alter table factura_modelos
        //    modify forma VARCHAR(255) not null;
        Schema::table('factura_modelos', function (Blueprint $table) {
            $table->dropColumn('forma');
            $table->dropColumn('uso');
            $table->string('forma')->nullable();
            $table->string('uso')->nullable();
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
