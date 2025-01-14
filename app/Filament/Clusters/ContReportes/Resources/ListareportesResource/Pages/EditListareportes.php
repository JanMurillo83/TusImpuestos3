<?php

namespace App\Filament\Clusters\ContReportes\Resources\ListareportesResource\Pages;

use App\Filament\Clusters\ContReportes\Resources\ListareportesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditListareportes extends EditRecord
{
    protected static string $resource = ListareportesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
