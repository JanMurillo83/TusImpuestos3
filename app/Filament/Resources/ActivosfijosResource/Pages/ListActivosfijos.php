<?php

namespace App\Filament\Resources\ActivosfijosResource\Pages;

use App\Filament\Resources\ActivosfijosResource;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\Terceros;
use Asmit\ResizedColumn\HasResizableColumn;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use App\Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListActivosfijos extends ListRecords
{
    protected static string $resource = ActivosfijosResource::class;
    use HasResizableColumn;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Agregar')
                ->icon('fas-plus')
                ->createAnother(false)
                ->after(function($record){
                    $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                    $dats = Carbon::now();
                    $fecha = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo.'-'.$dats->day;
                    $poliza = CatPolizas::create([
                        'tipo'=>'Dr',
                        'folio'=>$nopoliza,
                        'fecha'=>$fecha,
                        'concepto'=>'Registro de Activo Fijo',
                        'cargos'=>$record->importe,
                        'abonos'=>$record->importe,
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
                        'referencia'=>'S/F',
                        'uuid'=>'',
                        'tiposat'=>'Dr',
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    $polno = $poliza['id'];
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$record->cuentaact,
                            'cuenta'=>$record->descripcion,
                            'concepto'=>'Registro de Activo Fijo',
                            'cargo'=>$record->importe / 1.16,
                            'abono'=>0,
                            'factura'=>'S/F',
                            'nopartida'=>1,
                            'a_ejercicio'=>Filament::getTenant()->ejercicio,
                            'a_periodo'=>Filament::getTenant()->periodo,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>'11901000',
                            'cuenta'=>'IVA pendiente de pago',
                            'concepto'=>'Registro de Activo Fijo',
                            'cargo'=>($record->importe / 1.16) * 0.16,
                            'abono'=> 0,
                            'factura'=>'S/F',
                            'nopartida'=>2,
                            'a_ejercicio'=>Filament::getTenant()->ejercicio,
                            'a_periodo'=>Filament::getTenant()->periodo,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                        $prov = Terceros::where('id',$record->proveedor)->get()[0];
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$prov->cuenta,
                            'cuenta'=>$prov->nombre,
                            'concepto'=>'Registro de Activo Fijo',
                            'cargo'=>0,
                            'abono'=>$record->importe,
                            'factura'=>'S/F',
                            'nopartida'=>3,
                            'a_ejercicio'=>Filament::getTenant()->ejercicio,
                            'a_periodo'=>Filament::getTenant()->periodo,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                }),
        ];
    }
}
