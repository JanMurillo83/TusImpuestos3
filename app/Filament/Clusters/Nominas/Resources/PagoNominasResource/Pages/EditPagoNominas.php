<?php

namespace App\Filament\Clusters\Nominas\Resources\PagoNominasResource\Pages;

use App\Filament\Clusters\Nominas\Resources\PagoNominasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPagoNominas extends EditRecord
{
    protected static string $resource = PagoNominasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
