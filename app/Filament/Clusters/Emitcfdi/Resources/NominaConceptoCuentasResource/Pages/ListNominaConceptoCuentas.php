<?php

namespace App\Filament\Clusters\Emitcfdi\Resources\NominaConceptoCuentasResource\Pages;

use App\Filament\Clusters\Emitcfdi\Resources\NominaConceptoCuentasResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Resources\Pages\ListRecords;

class ListNominaConceptoCuentas extends ListRecords
{
    use HasResizableColumn;

    protected static string $resource = NominaConceptoCuentasResource::class;
}
