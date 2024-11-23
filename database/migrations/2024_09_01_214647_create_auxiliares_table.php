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
        Schema::create('auxiliares', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('cuenta')->nullable();
            $table->string('concepto')->nullable();
            $table->decimal('cargo', 18, 8);
            $table->decimal('abono', 18, 8);
            $table->string('factura')->nullable();
            $table->integer('nopartida')->nullable();
            $table->integer('cat_polizas_id')->default(0)->onDelete('cascade');
            $table->string('uuid')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('auxiliares_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auxiliares_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
        Schema::create('auxiliares_cat_polizas', function (Blueprint $table) {
            $table->id();
            $table->integer('auxiliares_id');
            $table->integer('cat_polizas_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliares');
    }
};
