<?php

use App\Filament\Clusters\Herramientas;
use App\Filament\Clusters\Herramientas\Pages\Tools;
use App\Models\User;

afterEach(function () {
    if (auth()->check()) {
        auth()->logout();
    }
});

it('no truena y no registra navegación de Herramientas si no hay usuario autenticado', function () {
    if (auth()->check()) {
        auth()->logout();
    }

    expect(Herramientas::shouldRegisterNavigation())->toBeFalse();
    expect(Tools::shouldRegisterNavigation())->toBeFalse();
});

it('registra navegación de Herramientas para administradores por is_admin aunque no tengan roles en pivote', function () {
    $user = new User();
    $user->is_admin = 1;
    $user->role = null;
    $user->setRelation('roles', collect());

    auth()->setUser($user);

    expect(Herramientas::shouldRegisterNavigation())->toBeTrue();
    expect(Tools::shouldRegisterNavigation())->toBeTrue();
    expect(Tools::canAccess())->toBeTrue();
});

it('registra navegación de Herramientas para administradores por columna role aunque no tengan roles en pivote', function () {
    $user = new User();
    $user->is_admin = 0;
    $user->role = 'administrador';
    $user->setRelation('roles', collect());

    auth()->setUser($user);

    expect(Herramientas::shouldRegisterNavigation())->toBeTrue();
    expect(Tools::shouldRegisterNavigation())->toBeTrue();
});

it('no registra navegación de Herramientas para rol no admin', function () {
    $user = new User();
    $user->is_admin = 0;
    $user->role = 'contador';
    $user->setRelation('roles', collect());

    auth()->setUser($user);

    expect(Herramientas::shouldRegisterNavigation())->toBeFalse();
    expect(Tools::shouldRegisterNavigation())->toBeFalse();
});
