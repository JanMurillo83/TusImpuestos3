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
        Schema::create('reg_traspasos', function (Blueprint $table) {
            $table->id();
            $table->integer('periodo');
            $table->integer('ejercicio');
            $table->integer('mov_ent');
            $table->integer('mov_sal');
            $table->integer('poliza');
            $table->integer('team_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_traspasos');
    }
};
