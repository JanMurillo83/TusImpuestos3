<?php

use App\Models\Clientes;
use App\Models\Inventario;
use App\Models\Proveedores;
use App\Services\Herramientas\CatalogosImportService;
use App\Services\Herramientas\CatalogosResetService;
use App\Services\Herramientas\MovimientosResetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('clientes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->string('clave')->nullable()->index();
        $table->string('nombre')->nullable();
        $table->string('rfc')->nullable();
        $table->timestamps();
    });

    Schema::create('proveedores', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->string('clave')->nullable()->index();
        $table->string('nombre')->nullable();
        $table->string('rfc')->nullable();
        $table->timestamps();
    });

    Schema::create('inventarios', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->string('clave')->nullable()->index();
        $table->string('descripcion')->nullable();
        $table->timestamps();
    });

    Schema::create('movbancos', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->timestamps();
    });

    Schema::create('cotizaciones', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->timestamps();
    });

    Schema::create('cotizaciones_partidas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->unsignedBigInteger('cotizaciones_id')->nullable();
        $table->timestamps();
    });

    Schema::create('cotizacion_actividades', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('cotizacion_id')->index();
        $table->timestamps();
    });

    Schema::create('facturas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->timestamps();
    });

    Schema::create('facturas_partidas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->unsignedBigInteger('facturas_id')->nullable();
        $table->timestamps();
    });

    Schema::create('ordenes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->timestamps();
    });

    Schema::create('ordenes_partidas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->unsignedBigInteger('ordenes_id')->nullable();
        $table->timestamps();
    });

    Schema::create('ordenes_insumos', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->timestamps();
    });

    Schema::create('ordenes_insumos_partidas', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id')->index();
        $table->unsignedBigInteger('ordenes_insumos_id')->nullable();
        $table->timestamps();
    });
});

it('importa clientes/proveedores/inventario desde CSV y hace update por clave+team', function () {
    $teamId = 1;

    Clientes::create([
        'team_id' => $teamId,
        'clave' => '1',
        'nombre' => 'Nombre Viejo',
        'rfc' => 'xxx010101xxx',
    ]);

    $tmpDir = sys_get_temp_dir();
    $clientesCsv = $tmpDir . '/clientes_import.csv';
    $proveedoresCsv = $tmpDir . '/proveedores_import.csv';
    $inventarioCsv = $tmpDir . '/inventario_import.csv';

    file_put_contents($clientesCsv, "clave,nombre,rfc\n1,Nombre Nuevo,aaa010101aaa\n");
    file_put_contents($proveedoresCsv, "clave,nombre,rfc\n10,Proveedor 10,bbb010101bbb\n");
    file_put_contents($inventarioCsv, "clave,descripcion\nP001,Producto 1\n");

    $service = app(CatalogosImportService::class);
    $res = $service->import($teamId, [
        'clientes' => $clientesCsv,
        'proveedores' => $proveedoresCsv,
        'inventario' => $inventarioCsv,
    ]);

    expect($res['clientes']['updated'])->toBe(1);
    expect($res['proveedores']['created'])->toBe(1);
    expect($res['inventario']['created'])->toBe(1);

    $cliente = Clientes::query()->where('team_id', $teamId)->where('clave', '1')->first();
    expect($cliente)->not->toBeNull();
    expect($cliente->nombre)->toBe('Nombre Nuevo');
    expect($cliente->rfc)->toBe('AAA010101AAA');

    $proveedor = Proveedores::query()->where('team_id', $teamId)->where('clave', '10')->first();
    expect($proveedor)->not->toBeNull();
    expect($proveedor->rfc)->toBe('BBB010101BBB');

    $producto = Inventario::query()->where('team_id', $teamId)->where('clave', 'P001')->first();
    expect($producto)->not->toBeNull();
    expect($producto->descripcion)->toBe('Producto 1');
});

it('purga movimientos solo del team indicado, incluyendo actividades de cotización', function () {
    $team1 = 1;
    $team2 = 2;

    // Movbancos
    DB::table('movbancos')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Cotizaciones + actividades
    $cot1 = DB::table('cotizaciones')->insertGetId(['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()]);
    $cot2 = DB::table('cotizaciones')->insertGetId(['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('cotizacion_actividades')->insert([
        ['cotizacion_id' => $cot1, 'created_at' => now(), 'updated_at' => now()],
        ['cotizacion_id' => $cot2, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Detalles por team
    DB::table('cotizaciones_partidas')->insert([
        ['team_id' => $team1, 'cotizaciones_id' => $cot1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'cotizaciones_id' => $cot2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('facturas')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('facturas_partidas')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('ordenes')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('ordenes_partidas')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('ordenes_insumos')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('ordenes_insumos_partidas')->insert([
        ['team_id' => $team1, 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $service = app(MovimientosResetService::class);
    $counts = $service->purgeForTeam($team1);

    expect($counts['movbancos'])->toBe(1);
    expect(DB::table('movbancos')->where('team_id', $team1)->count())->toBe(0);
    expect(DB::table('movbancos')->where('team_id', $team2)->count())->toBe(1);

    // cotización + actividad del team1 se elimina; la del team2 queda
    expect(DB::table('cotizaciones')->where('team_id', $team1)->count())->toBe(0);
    expect(DB::table('cotizaciones')->where('team_id', $team2)->count())->toBe(1);
    expect(DB::table('cotizacion_actividades')->where('cotizacion_id', $cot1)->count())->toBe(0);
    expect(DB::table('cotizacion_actividades')->where('cotizacion_id', $cot2)->count())->toBe(1);
});

it('purga por separado cada catálogo solo del team indicado', function () {
    $team1 = 1;
    $team2 = 2;

    Clientes::insert([
        ['team_id' => $team1, 'clave' => 'C1', 'nombre' => 'Cliente 1', 'rfc' => 'AAA010101AAA', 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'clave' => 'C2', 'nombre' => 'Cliente 2', 'rfc' => 'BBB010101BBB', 'created_at' => now(), 'updated_at' => now()],
    ]);
    Proveedores::insert([
        ['team_id' => $team1, 'clave' => 'P1', 'nombre' => 'Prov 1', 'rfc' => 'CCC010101CCC', 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'clave' => 'P2', 'nombre' => 'Prov 2', 'rfc' => 'DDD010101DDD', 'created_at' => now(), 'updated_at' => now()],
    ]);
    Inventario::insert([
        ['team_id' => $team1, 'clave' => 'I1', 'descripcion' => 'Prod 1', 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => $team2, 'clave' => 'I2', 'descripcion' => 'Prod 2', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $service = app(CatalogosResetService::class);
    $counts = $service->purgeForTeam($team1, ['clientes' => true]);

    expect($counts['clientes'])->toBe(1);
    expect(Clientes::query()->where('team_id', $team1)->count())->toBe(0);
    expect(Clientes::query()->where('team_id', $team2)->count())->toBe(1);

    // Los otros catálogos no deben verse afectados
    expect(Proveedores::query()->where('team_id', $team1)->count())->toBe(1);
    expect(Inventario::query()->where('team_id', $team1)->count())->toBe(1);
});
