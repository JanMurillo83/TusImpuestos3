<?php

namespace App\Filament\Resources\ReembolsosResource\Pages;

use App\Filament\Resources\ReembolsosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReembolsos extends ListRecords
{
    protected static string $resource = ReembolsosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
