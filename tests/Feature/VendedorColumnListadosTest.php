<?php

it('muestra la columna Vendedor en el listado de Cotizaciones', function () {
    $contents = file_get_contents(base_path('app/Filament/Clusters/tiadmin/Resources/CotizacionesResource.php'));

    expect($contents)->toContain("TextColumn::make('vendedor_elaboro')");
    expect($contents)->toContain("->label('Vendedor')");
});

it('muestra la columna Vendedor en el listado de Facturas', function () {
    $contents = file_get_contents(base_path('app/Filament/Clusters/tiadmin/Resources/FacturasResource.php'));

    expect($contents)->toContain("TextColumn::make('vendedor_elaboro')");
    expect($contents)->toContain("->label('Vendedor')");
});
