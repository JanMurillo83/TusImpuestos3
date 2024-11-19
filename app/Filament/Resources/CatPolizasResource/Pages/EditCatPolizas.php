<?php

namespace App\Filament\Resources\CatPolizasResource\Pages;

use App\Filament\Resources\CatPolizasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
}
