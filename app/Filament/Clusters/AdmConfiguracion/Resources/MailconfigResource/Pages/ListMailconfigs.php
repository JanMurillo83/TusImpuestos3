<?php

namespace App\Filament\Clusters\AdmConfiguracion\Resources\MailconfigResource\Pages;

use App\Filament\Clusters\AdmConfiguracion\Resources\MailconfigResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
