<?php

namespace App\Filament\Clusters\tiadmin\Resources\MovinventarioResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\MovinventarioResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListMovinventarios extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = MovinventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
}
