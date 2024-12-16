<?php

namespace App\Filament\Clusters\AdmMovimientos\Resources\ConceptosmiResource\Pages;

use App\Filament\Clusters\AdmMovimientos\Resources\ConceptosmiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConceptosmi extends EditRecord
{
    protected static string $resource = ConceptosmiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
