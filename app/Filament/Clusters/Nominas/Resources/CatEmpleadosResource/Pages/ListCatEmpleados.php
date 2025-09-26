<?php

namespace App\Filament\Clusters\Nominas\Resources\CatEmpleadosResource\Pages;

use App\Filament\Clusters\Nominas\Resources\CatEmpleadosResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCatEmpleados extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = CatEmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
