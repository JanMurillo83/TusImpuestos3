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
use Illuminate\Validation\ValidationException;

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
        CatPolizasResource::syncPartidasMeta($this->record);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $detalle = $data['detalle'] ?? [];
        $cargos = 0;
        $abonos = 0;

        foreach ($detalle as $partida) {
            $cargos += floatval($partida['cargo'] ?? 0);
            $abonos += floatval($partida['abono'] ?? 0);
        }

        $cargos = bcdiv($cargos, 1, 2);
        $abonos = bcdiv($abonos, 1, 2);

        if ($cargos != $abonos) {
            throw ValidationException::withMessages([
                'detalle' => 'Cargos y abonos no cuadran. Revise las partidas antes de guardar.',
            ]);
        }

        $data['cargos'] = $cargos;
        $data['abonos'] = $abonos;

        return $data;
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
