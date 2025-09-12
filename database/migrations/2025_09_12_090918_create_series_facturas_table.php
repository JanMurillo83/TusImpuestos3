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
        Schema::create('series_facturas', function (Blueprint $table) {
            $table->id();
            $table->string('serie');
            $table->string('tipo');
            $table->integer('folio');
            $table->integer('team_id');
            $table->timestamps();
        });

        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 5240, 'team_id' => 17]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 4977, 'team_id' => 16]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 3901, 'team_id' => 18]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 2328, 'team_id' => 9]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 2382, 'team_id' => 22]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 170, 'team_id' => 12]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 3470, 'team_id' => 19]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 4698, 'team_id' => 8]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 442, 'team_id' => 4]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 402, 'team_id' => 13]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 1942, 'team_id' => 20]);
        \App\Models\SeriesFacturas::create(['serie' => 'A', 'tipo' => 'F', 'folio' => 671, 'team_id' => 11]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series_facturas');
    }
};
