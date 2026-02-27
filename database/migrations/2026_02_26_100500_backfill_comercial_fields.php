<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cotizaciones') && Schema::hasColumn('cotizaciones', 'estado_comercial')) {
            DB::statement("UPDATE cotizaciones SET estado_comercial = CASE estado WHEN 'Activa' THEN 'OPEN' WHEN 'Cerrada' THEN 'WON' WHEN 'Cancelada' THEN 'LOST' ELSE 'OPEN' END WHERE estado_comercial IS NULL OR estado_comercial = ''");
        }
        if (Schema::hasTable('cotizaciones') && Schema::hasColumn('cotizaciones', 'probabilidad')) {
            DB::table('cotizaciones')->whereNull('probabilidad')->update(['probabilidad' => 0.20]);
        }
        if (Schema::hasTable('cotizaciones') && Schema::hasColumn('cotizaciones', 'descuento_pct')) {
            DB::table('cotizaciones')->whereNull('descuento_pct')->update(['descuento_pct' => 0]);
        }
        if (Schema::hasTable('cotizaciones') && Schema::hasColumn('cotizaciones', 'created_by_user_id')) {
            DB::statement("UPDATE cotizaciones c SET created_by_user_id = (SELECT u.id FROM users u WHERE u.name = c.nombre_elaboro LIMIT 1) WHERE c.created_by_user_id IS NULL AND c.nombre_elaboro IS NOT NULL AND c.nombre_elaboro <> ''");
            DB::statement("UPDATE cotizaciones c JOIN users u ON u.id = c.created_by_user_id SET c.nombre_elaboro = u.name WHERE (c.nombre_elaboro IS NULL OR c.nombre_elaboro = '')");
        }

        if (Schema::hasTable('facturas') && Schema::hasColumn('facturas', 'segmento_id') && Schema::hasColumn('cotizaciones', 'segmento_id')) {
            DB::statement("UPDATE facturas f JOIN cotizaciones c ON c.id = f.cotizacion_id SET f.segmento_id = c.segmento_id WHERE f.segmento_id IS NULL AND c.segmento_id IS NOT NULL");
        }
        if (Schema::hasTable('facturas') && Schema::hasColumn('facturas', 'canal_id') && Schema::hasColumn('cotizaciones', 'canal_id')) {
            DB::statement("UPDATE facturas f JOIN cotizaciones c ON c.id = f.cotizacion_id SET f.canal_id = c.canal_id WHERE f.canal_id IS NULL AND c.canal_id IS NOT NULL");
        }

        if (Schema::hasTable('facturas') && Schema::hasColumn('facturas', 'margen_pct') && Schema::hasColumn('facturas', 'cobranza_pct')) {
            DB::statement("UPDATE facturas f LEFT JOIN (SELECT facturas_id, COALESCE(SUM(costo * cant), 0) AS costo_total FROM facturas_partidas GROUP BY facturas_id) p ON p.facturas_id = f.id SET f.margen_pct = CASE WHEN f.subtotal > 0 THEN LEAST(1, GREATEST(0, (f.subtotal - IFNULL(p.costo_total,0)) / f.subtotal)) ELSE 0 END, f.cobranza_pct = CASE WHEN f.total > 0 THEN LEAST(1, GREATEST(0, 1 - (IFNULL(f.pendiente_pago,0) / f.total))) ELSE 0 END");
        }
    }

    public function down(): void
    {
        // Backfill sin rollback
    }
};
