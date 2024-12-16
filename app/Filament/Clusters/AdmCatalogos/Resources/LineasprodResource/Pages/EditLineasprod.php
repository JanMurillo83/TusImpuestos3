<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\LineasprodResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\LineasprodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLineasprod extends EditRecord
{
    protected static string $resource = LineasprodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
