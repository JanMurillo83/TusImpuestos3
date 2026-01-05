<?php

namespace App\Filament\Clusters\tiadmin\Resources\ConceptosmiResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ConceptosmiResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConceptosmis extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = ConceptosmiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
