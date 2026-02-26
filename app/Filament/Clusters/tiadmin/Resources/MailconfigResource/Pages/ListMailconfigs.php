<?php

namespace App\Filament\Clusters\tiadmin\Resources\MailconfigResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\MailconfigResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use App\Filament\Resources\Pages\ListRecords;

class ListMailconfigs extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = MailconfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\CreateAction::make(),
        ];
    }
}
