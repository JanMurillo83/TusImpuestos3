<?php

namespace App\Filament\Resources\TercerosResource\Pages;

use App\Filament\Resources\TercerosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTerceros extends ListRecords
{
    protected static string $resource = TercerosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
