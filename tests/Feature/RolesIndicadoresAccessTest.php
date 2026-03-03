<?php

use App\Filament\Clusters\tiadmin\Pages\ComercialDashboard;
use App\Filament\Clusters\tiadmin\Pages\DashboardInicio;
use App\Filament\Clusters\tiadmin\Resources\ComprasResource;
use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource;
use App\Filament\Clusters\tiadmin\Resources\FacturasResource;
use App\Filament\Clusters\tiadmin\Resources\InventarioResource;
use App\Filament\Clusters\tiadmin\Resources\OrdenesResource;
use App\Filament\Pages\DashBoardIndicadores;
use App\Filament\Pages\IndicadoresMain;
use App\Filament\Pages\InventarioDetalle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('password')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->boolean('is_admin')->default(false);
        $table->string('role')->nullable();
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('description')->nullable();
        $table->timestamps();
    });

    Schema::create('role_user', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('role_id');
        $table->unique(['user_id', 'role_id']);
    });

    foreach ([
        'administrador',
        'contador',
        'ventas',
        'compras',
        'facturista',
        'compras_cotizaciones',
    ] as $name) {
        Role::create(['name' => $name, 'description' => $name]);
    }
});

it('permite al rol compras_cotizaciones ver solo menús de Compras y Cotizaciones', function () {
    $user = User::create(['name' => 'U', 'email' => 'u@test.local']);
    $user->roles()->attach(Role::where('name', 'compras_cotizaciones')->value('id'));

    $this->actingAs($user);

    expect(ComprasResource::canViewAny())->toBeTrue();
    expect(OrdenesResource::canViewAny())->toBeTrue();
    expect(CotizacionesResource::canViewAny())->toBeTrue();

    expect(FacturasResource::canViewAny())->toBeFalse();
    expect(InventarioResource::canViewAny())->toBeFalse();
    expect(DashboardInicio::canAccess())->toBeFalse();
});

it('restringe páginas de indicadores/embeds por rol', function () {
    $compras = User::create(['name' => 'Compras', 'email' => 'c@test.local']);
    $compras->roles()->attach(Role::where('name', 'compras')->value('id'));

    $ventas = User::create(['name' => 'Ventas', 'email' => 'v@test.local']);
    $ventas->roles()->attach(Role::where('name', 'ventas')->value('id'));

    $contador = User::create(['name' => 'Contador', 'email' => 'ct@test.local']);
    $contador->roles()->attach(Role::where('name', 'contador')->value('id'));

    $facturista = User::create(['name' => 'Facturista', 'email' => 'f@test.local']);
    $facturista->roles()->attach(Role::where('name', 'facturista')->value('id'));

    $this->actingAs($compras);
    expect(ComercialDashboard::canAccess())->toBeFalse();
    expect(DashBoardIndicadores::canAccess())->toBeFalse();
    expect(InventarioDetalle::canAccess())->toBeTrue();
    expect(IndicadoresMain::canAccess())->toBeFalse();

    $this->actingAs($ventas);
    expect(ComercialDashboard::canAccess())->toBeTrue();
    expect(DashBoardIndicadores::canAccess())->toBeFalse();
    expect(InventarioDetalle::canAccess())->toBeTrue();
    expect(IndicadoresMain::canAccess())->toBeTrue();

    $this->actingAs($contador);
    expect(DashBoardIndicadores::canAccess())->toBeTrue();
    expect(ComercialDashboard::canAccess())->toBeTrue();
    expect(InventarioDetalle::canAccess())->toBeTrue();

    $this->actingAs($facturista);
    expect(DashBoardIndicadores::canAccess())->toBeFalse();
    expect(ComercialDashboard::canAccess())->toBeTrue();
    expect(InventarioDetalle::canAccess())->toBeFalse();
});
