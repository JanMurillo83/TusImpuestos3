<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditCatPolizas extends EditRecord
{
    protected static string $resource = CatPolizasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
            ->before(function(Model $record){
                $Pid = $record->id;
                $RelIds = DB::table('auxiliares_cat_polizas')->where('cat_polizas_id',$Pid)->get();
                foreach($RelIds as $RelId)
                {
                    DB::table('auxiliares_cat_polizas')->where('id',$RelId->id)->delete();
                    DB::table('auxiliares')->where('id',$RelId->auxiliares_id)->delete();
                }
            }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        CatPolizasResource::syncPartidasMeta($this->record);
    }
}
