<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listas_precios', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('lista');
            $table->string('nombre');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'lista']);
        });

        $defaults = [
            1 => 'Precio Publico',
            2 => 'Precio2',
            3 => 'Precio3',
            4 => 'Precio4',
            5 => 'Precio5',
        ];

        $teams = DB::table('teams')->pluck('id');
        if ($teams->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $rows = [];
        foreach ($teams as $teamId) {
            foreach ($defaults as $lista => $nombre) {
                $rows[] = [
                    'team_id' => $teamId,
                    'lista' => $lista,
                    'nombre' => $nombre,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('listas_precios')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listas_precios');
    }
};
