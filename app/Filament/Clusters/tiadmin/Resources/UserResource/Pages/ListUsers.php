<?php

namespace App\Filament\Clusters\tiadmin\Resources\UserResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\UserResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
}
