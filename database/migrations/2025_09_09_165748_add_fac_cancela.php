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
        Schema::table('facturas', function (Blueprint $table) {
            $table->dateTime('fecha_cancela')->after('tcambio')->nullable();
            $table->string('motivo')->after('fecha_cancela')->nullable();
            $table->string('sustituye')->after('motivo')->nullable();
            $table->text('xml_cancela')->after('sustituye')->nullable();
        });
        Schema::table('pagos', function (Blueprint $table) {
            $table->dateTime('fecha_cancela')->after('tcambio')->nullable();
            $table->string('motivo')->after('fecha_cancela')->nullable();
            $table->string('sustituye')->after('motivo')->nullable();
            $table->text('xml_cancela')->after('sustituye')->nullable();
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
