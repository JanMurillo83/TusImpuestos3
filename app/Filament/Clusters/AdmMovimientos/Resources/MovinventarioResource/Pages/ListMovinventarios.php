<?php

namespace App\Filament\Clusters\AdmMovimientos\Resources\MovinventarioResource\Pages;

use App\Filament\Clusters\AdmMovimientos\Resources\MovinventarioResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
