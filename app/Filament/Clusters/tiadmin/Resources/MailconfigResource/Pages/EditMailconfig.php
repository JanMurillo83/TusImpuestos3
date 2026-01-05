<?php

namespace App\Filament\Clusters\tiadmin\Resources\MailconfigResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\MailconfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailconfig extends EditRecord
{
    protected static string $resource = MailconfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
