<?php

namespace App\Filament\Clusters\ContReportes\Resources\ListareportesResource\Pages;

use App\Filament\Clusters\ContReportes\Resources\ListareportesResource;
use App\Http\Controllers\NuevoReportes;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListareportes extends ListRecords
{
    protected static string $resource = ListareportesResource::class;
    public $funciones = NuevoReportes::class;
    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
