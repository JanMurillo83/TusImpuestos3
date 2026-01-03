<?php

namespace App\Filament\Clusters\tiadmin\Resources\ComprasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ComprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompras extends EditRecord
{
    protected static string $resource = ComprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
