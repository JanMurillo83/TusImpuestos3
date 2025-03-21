<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;

class CreateCatPolizas extends CreateRecord
{
    protected static string $resource = CatPolizasResource::class;
    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
        ->hidden();
    }
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Grabar  ')
            ->color('primary')
            ->icon('fas-save')
            ->submit(null)
            ->requiresConfirmation()
            ->action(function(){
                $this->create();
                $this->closeActionModal();

            })->size('lg');
    }
    protected function afterCreate(): void
    {
        $partidas = $this->record['partidas'];
        $cargos = 0;
        $abonos = 0;
        $part = 0;
        foreach($partidas as $partida)
        {
            $part++;
            $partida['cat_polizas_id'] = $this->record->getKey();
            $partida['nopartida'] = $part;
            $cargos += $partida['cargo'];
            $abonos += $partida['abono'];
        }
        $this->record['cargos'] = $cargos;
        $this->record['abonos'] = $abonos;
        $this->record->save();
    }
    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->color('danger')
            ->icon('fas-ban')
            ->submit(null)
            //->requiresConfirmation()
            ->action(function(){
                $this->closeActionModal();
                //$this->create();
            })->size('lg');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Registro Grabado')
            ->body('Se realizo el alta del registro correctamente')
            ->duration(2000);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
        //return url('../facturas');
    }
}
