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
            $table->string('poliza');
            $table->string('codigo');
            $table->string('cuenta');
            $table->string('concepto');
            $table->decimal('cargo', 18, 8);
            $table->decimal('abono', 18, 8);
            $table->string('factura');
            $table->integer('nopartida')->nullable();
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('auxiliares_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auxiliares_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliares_nima831222hz9');
    }
};
