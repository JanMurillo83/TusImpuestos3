<?php

use App\Models\Compras;
use App\Models\Facturas;
use App\Services\Reportes\UtilidadBrutaService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('facturas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->date('fecha')->index();
        $table->string('estado')->nullable();
        $table->string('moneda')->nullable();
        $table->decimal('tcambio', 18, 6)->nullable();
        $table->decimal('subtotal', 18, 2)->nullable();
        $table->string('serie')->nullable();
        $table->string('folio')->nullable();
        $table->string('docto')->nullable();
        $table->string('nombre')->nullable();
        $table->timestamps();
    });

    Schema::create('compras', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->date('fecha')->index();
        $table->string('estado')->nullable();
        $table->unsignedBigInteger('orden_id')->nullable()->index();
        $table->string('moneda')->nullable();
        $table->decimal('tcambio', 18, 6)->nullable();
        $table->decimal('subtotal', 18, 2)->nullable();
        $table->string('serie')->nullable();
        $table->string('folio')->nullable();
        $table->string('docto')->nullable();
        $table->string('nombre')->nullable();
        $table->timestamps();
    });
});

it('calcula utilidad bruta por periodo usando facturas timbradas y compras activas ligadas a OC', function () {
    $teamId = 1;

    // Ventas dentro del periodo
    Facturas::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-10',
        'estado' => 'Timbrada',
        'moneda' => 'MXN',
        'tcambio' => 1,
        'subtotal' => 1000,
        'docto' => 'F0001',
        'nombre' => 'Cliente 1',
    ]);
    Facturas::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-12',
        'estado' => 'Timbrada',
        'moneda' => 'USD',
        'tcambio' => 20,
        'subtotal' => 10, // 200 MXN
        'docto' => 'F0002',
        'nombre' => 'Cliente 2',
    ]);

    // No debe contar: no timbrada
    Facturas::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-11',
        'estado' => 'Activa',
        'moneda' => 'MXN',
        'tcambio' => 1,
        'subtotal' => 9999,
        'docto' => 'FXXXX',
        'nombre' => 'Cliente X',
    ]);

    // Costos dentro del periodo (solo compras activas con orden_id)
    Compras::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-09',
        'estado' => 'Activa',
        'orden_id' => 123,
        'moneda' => 'MXN',
        'tcambio' => 1,
        'subtotal' => 400,
        'docto' => 'C0001',
        'nombre' => 'Proveedor 1',
    ]);
    Compras::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-13',
        'estado' => 'Activa',
        'orden_id' => 124,
        'moneda' => 'USD',
        'tcambio' => 20,
        'subtotal' => 10, // 200 MXN
        'docto' => 'C0002',
        'nombre' => 'Proveedor 2',
    ]);

    // No debe contar: sin orden_id (no viene de OC)
    Compras::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-14',
        'estado' => 'Activa',
        'orden_id' => null,
        'moneda' => 'MXN',
        'tcambio' => 1,
        'subtotal' => 9999,
        'docto' => 'CXXXX',
        'nombre' => 'Proveedor X',
    ]);

    // No debe contar: cancelada
    Compras::create([
        'team_id' => $teamId,
        'fecha' => '2026-03-15',
        'estado' => 'Cancelada',
        'orden_id' => 125,
        'moneda' => 'MXN',
        'tcambio' => 1,
        'subtotal' => 9999,
        'docto' => 'CYYYY',
        'nombre' => 'Proveedor Y',
    ]);

    // Fuera del periodo
    Facturas::create([
        'team_id' => $teamId,
        'fecha' => '2026-02-28',
        'estado' => 'Timbrada',
        'moneda' => 'MXN',
        'tcambio' => 1,
        'subtotal' => 5000,
        'docto' => 'FOUT',
        'nombre' => 'Cliente OUT',
    ]);

    $service = app(UtilidadBrutaService::class);
    $reporte = $service->build($teamId, '2026-03-01', '2026-03-31');

    expect($reporte['ventas'])->toHaveCount(2);
    expect($reporte['costos'])->toHaveCount(2);

    expect((float) $reporte['totales']['ventas_mxn'])->toBe(1200.0);
    expect((float) $reporte['totales']['costos_mxn'])->toBe(600.0);
    expect((float) $reporte['totales']['utilidad_mxn'])->toBe(600.0);
    expect(round((float) $reporte['totales']['margen'], 4))->toBe(0.5);
});
