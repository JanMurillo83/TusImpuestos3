<?php

namespace App\Filament\Clusters\tiadmin\Resources\OrdenesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\OrdenesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenes extends EditRecord
{
    protected static string $resource = OrdenesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
