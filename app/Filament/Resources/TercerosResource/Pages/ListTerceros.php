<?php

namespace App\Filament\Resources\TercerosResource\Pages;

use App\Filament\Resources\TercerosResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListTerceros extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = TercerosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Agregar')
            ->icon('fas-plus')
            ->createAnother(false),
        ];
    }
}
