<?php

namespace App\Filament\Clusters\tiadmin\Resources\PedidosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\PedidosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPedidos extends EditRecord
{
    protected static string $resource = PedidosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
