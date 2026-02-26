<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisiciones', function (Blueprint $table) {
            if (! Schema::hasColumn('requisiciones', 'serie')) {
                $table->string('serie')->nullable()->after('id');
            }
            if (! Schema::hasColumn('requisiciones', 'docto')) {
                $table->string('docto')->nullable()->after('folio');
            }
        });

        Schema::table('ordenes', function (Blueprint $table) {
            if (! Schema::hasColumn('ordenes', 'serie')) {
                $table->string('serie')->nullable()->after('id');
            }
            if (! Schema::hasColumn('ordenes', 'docto')) {
                $table->string('docto')->nullable()->after('folio');
            }
        });

        Schema::table('ordenes_insumos', function (Blueprint $table) {
            if (! Schema::hasColumn('ordenes_insumos', 'serie')) {
                $table->string('serie')->nullable()->after('id');
            }
            if (! Schema::hasColumn('ordenes_insumos', 'docto')) {
                $table->string('docto')->nullable()->after('folio');
            }
        });

        Schema::table('compras', function (Blueprint $table) {
            if (! Schema::hasColumn('compras', 'serie')) {
                $table->string('serie')->nullable()->after('id');
            }
            if (! Schema::hasColumn('compras', 'docto')) {
                $table->string('docto')->nullable()->after('folio');
            }
        });

        if (Schema::hasTable('requisiciones')) {
            DB::table('requisiciones')
                ->whereNull('serie')
                ->update([
                    'serie' => 'RQ',
                    'docto' => DB::raw("CONCAT('RQ', folio)"),
                ]);
            DB::table('requisiciones')
                ->whereNull('docto')
                ->whereNotNull('serie')
                ->update([
                    'docto' => DB::raw("CONCAT(serie, folio)"),
                ]);
        }

        if (Schema::hasTable('ordenes')) {
            DB::table('ordenes')
                ->whereNull('serie')
                ->update([
                    'serie' => 'OC',
                    'docto' => DB::raw("CONCAT('OC', folio)"),
                ]);
            DB::table('ordenes')
                ->whereNull('docto')
                ->whereNotNull('serie')
                ->update([
                    'docto' => DB::raw("CONCAT(serie, folio)"),
                ]);
        }

        if (Schema::hasTable('ordenes_insumos')) {
            DB::table('ordenes_insumos')
                ->whereNull('serie')
                ->update([
                    'serie' => 'OI',
                    'docto' => DB::raw("CONCAT('OI', folio)"),
                ]);
            DB::table('ordenes_insumos')
                ->whereNull('docto')
                ->whereNotNull('serie')
                ->update([
                    'docto' => DB::raw("CONCAT(serie, folio)"),
                ]);
        }

        if (Schema::hasTable('compras')) {
            DB::table('compras')
                ->whereNull('serie')
                ->update([
                    'serie' => 'E',
                    'docto' => DB::raw("CONCAT('E', folio)"),
                ]);
            DB::table('compras')
                ->whereNull('docto')
                ->whereNotNull('serie')
                ->update([
                    'docto' => DB::raw("CONCAT(serie, folio)"),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('requisiciones', function (Blueprint $table) {
            if (Schema::hasColumn('requisiciones', 'docto')) {
                $table->dropColumn('docto');
            }
            if (Schema::hasColumn('requisiciones', 'serie')) {
                $table->dropColumn('serie');
            }
        });

        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'docto')) {
                $table->dropColumn('docto');
            }
            if (Schema::hasColumn('ordenes', 'serie')) {
                $table->dropColumn('serie');
            }
        });

        Schema::table('ordenes_insumos', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_insumos', 'docto')) {
                $table->dropColumn('docto');
            }
            if (Schema::hasColumn('ordenes_insumos', 'serie')) {
                $table->dropColumn('serie');
            }
        });

        Schema::table('compras', function (Blueprint $table) {
            if (Schema::hasColumn('compras', 'docto')) {
                $table->dropColumn('docto');
            }
            if (Schema::hasColumn('compras', 'serie')) {
                $table->dropColumn('serie');
            }
        });
    }
};
