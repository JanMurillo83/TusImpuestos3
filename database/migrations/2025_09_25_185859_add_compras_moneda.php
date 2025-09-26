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
        Schema::table('ordenes', function (Blueprint $table) {
            $table->string('moneda')->after('total')->default('MXN');
            $table->decimal('tcambio')->after('moneda')->default(1);
        });
        Schema::table('ordenes_partidas', function (Blueprint $table) {
            $table->string('moneda')->after('total')->default('MXN');
            $table->decimal('tcambio')->after('moneda')->default(1);
        });
        Schema::table('compras', function (Blueprint $table) {
            $table->string('moneda')->after('total')->default('MXN');
            $table->decimal('tcambio')->after('moneda')->default(1);
        });
        Schema::table('compras_partidas', function (Blueprint $table) {
            $table->string('moneda')->after('total')->default('MXN');
            $table->decimal('tcambio')->after('moneda')->default(1);
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
