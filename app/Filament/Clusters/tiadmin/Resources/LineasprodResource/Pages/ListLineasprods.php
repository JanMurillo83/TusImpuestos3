<?php

namespace App\Filament\Clusters\tiadmin\Resources\LineasprodResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\LineasprodResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListLineasprods extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = LineasprodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
