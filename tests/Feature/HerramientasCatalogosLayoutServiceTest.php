<?php

use App\Services\Herramientas\CatalogosLayoutService;

dataset('catalogos_layout', [
    'inventario',
    'clientes',
    'proveedores',
]);

it('genera un layout CSV con headers esperados y una fila vacía', function (string $catalogo) {
    $service = app(CatalogosLayoutService::class);
    $csv = $service->toCsv($catalogo);

    $lines = preg_split("/\r\n|\n|\r/", trim($csv));
    $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

    expect($lines)->toHaveCount(2);

    $headers = str_getcsv($lines[0]);
    expect($headers)->toBe($service->headers($catalogo));

    $row = str_getcsv($lines[1]);
    expect($row)->toBe(array_fill(0, count($headers), ''));
})->with('catalogos_layout');
