<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teams')) {
            return;
        }
        if (! Schema::hasTable('comercial_segmentos') || ! Schema::hasTable('comercial_canales') ||
            ! Schema::hasTable('comercial_motivos_ganada') || ! Schema::hasTable('comercial_motivos_perdida')) {
            return;
        }

        $segmentos = ['PyME', 'Corporativo', 'Startup', 'Persona Fisica', 'Gobierno'];
        $canales = ['WhatsApp', 'Referido', 'Meta Ads', 'Organico', 'Alianza', 'Evento'];
        $motivosGanada = ['Rapidez', 'Confianza', 'Propuesta clara', 'Financiamiento', 'Precio competitivo', 'Experiencia', 'Atencion / seguimiento'];
        $motivosPerdida = ['Precio', 'Competencia', 'Sin presupuesto', 'Decision postergada', 'Requisitos / compliance', 'No responde', 'Tiempos / urgencia', 'Falta de confianza'];

        $teamIds = DB::table('teams')->pluck('id');
        if ($teamIds->isEmpty()) {
            return;
        }

        $now = now();
        foreach ($teamIds as $teamId) {
            foreach ($segmentos as $idx => $nombre) {
                DB::table('comercial_segmentos')->updateOrInsert(
                    ['team_id' => $teamId, 'nombre' => $nombre],
                    ['activo' => 1, 'sort' => $idx + 1, 'updated_at' => $now, 'created_at' => $now]
                );
            }
            foreach ($canales as $idx => $nombre) {
                DB::table('comercial_canales')->updateOrInsert(
                    ['team_id' => $teamId, 'nombre' => $nombre],
                    ['activo' => 1, 'sort' => $idx + 1, 'updated_at' => $now, 'created_at' => $now]
                );
            }
            foreach ($motivosGanada as $idx => $nombre) {
                DB::table('comercial_motivos_ganada')->updateOrInsert(
                    ['team_id' => $teamId, 'nombre' => $nombre],
                    ['activo' => 1, 'sort' => $idx + 1, 'updated_at' => $now, 'created_at' => $now]
                );
            }
            foreach ($motivosPerdida as $idx => $nombre) {
                DB::table('comercial_motivos_perdida')->updateOrInsert(
                    ['team_id' => $teamId, 'nombre' => $nombre],
                    ['activo' => 1, 'sort' => $idx + 1, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Sin rollback automatico por seguridad de datos
    }
};
