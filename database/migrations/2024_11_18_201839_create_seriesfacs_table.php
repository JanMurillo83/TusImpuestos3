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
        Schema::create('seriesfacs', function (Blueprint $table) {
            $table->id();
            $table->string('serie')->nullable();
            $table->string('tipo')->nullable();
            $table->integer('folio')->default(0);
            $table->integer('team_id')->default(0);
            $table->timestamps();
        });
        Schema::create('seriesfac_team', function (Blueprint $table) {
            $table->foreignId('seriesfac_id')->constrained();
            $table->foreignId('team_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seriesfacs');
    }
};
