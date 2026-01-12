<?php

namespace App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurtidoInve extends EditRecord
{
    protected static string $resource = SurtidoInveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
