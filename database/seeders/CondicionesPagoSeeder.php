<?php

namespace Database\Seeders;

use App\Models\Team;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CondicionesPagoSeeder extends Seeder
{
    public function run(): void
    {
        $condiciones = [
            'CONTADO',
            'CRÉDITO 5 DÍAS',
            'CRÉDITO 10 DÍAS',
            'CRÉDITO 15 DÍAS',
            'CRÉDITO 20 DÍAS',
            'CRÉDITO 30 DÍAS',
            'CRÉDITO 45 DÍAS',
            'CRÉDITO 60 DÍAS',
            'CRÉDITO 90 DÍAS',
            'CRÉDITO 120 DÍAS',
        ];

        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));

        $teamIds = Team::query()->pluck('id');
        foreach ($teamIds as $teamId) {
            foreach ($condiciones as $index => $nombre) {
                DB::table('condiciones_pagos')->updateOrInsert(
                    ['team_id' => $teamId, 'nombre' => $nombre],
                    [
                        'activo' => true,
                        'sort' => $index + 1,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            }
        }
    }
}
