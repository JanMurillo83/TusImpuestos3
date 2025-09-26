<?php

namespace App\Filament\Clusters\Nominas\Resources\PagoNominasResource\Pages;

use App\Filament\Clusters\Nominas\Resources\PagoNominasResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPagoNominas extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = PagoNominasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
