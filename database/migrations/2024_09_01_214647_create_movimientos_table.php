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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 100);
            $table->string('elemento', 100);
            $table->string('descripcion_elemento');
            $table->string('etapa_elemento');
            $table->string('descripcion', 100);
            $table->integer('cantidad');
            $table->decimal('costo', 11)->nullable();
            $table->string('usuario', 100);
            $table->date('date');
            $table->string('fecha', 10);
            $table->time('hora')->nullable();
            $table->boolean('cancelado')->default(false);
            $table->string('usuario_cancelado');
            $table->integer('id_item');
            $table->foreignId('team_id')->constrained()->nullable();
            $table->timestamps();
        });
        Schema::create('movimientos_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimientos_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
