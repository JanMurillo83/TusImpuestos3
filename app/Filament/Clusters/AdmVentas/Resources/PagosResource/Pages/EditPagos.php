<?php

namespace App\Filament\Clusters\AdmVentas\Resources\PagosResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\PagosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPagos extends EditRecord
{
    protected static string $resource = PagosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
