<?php

namespace App\Filament\Resources\ComprasResource\Pages;

use App\Filament\Resources\ComprasResource;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\DB;

class ListCompras extends ListRecords
{
    protected static string $resource = ComprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Agregar')
            ->icon('fas-plus')
            ->createAnother(false)
            ->modalSubmitActionLabel('Guardar')
            ->modalCancelActionLabel('Cancelar')
            ->modalFooterActionsAlignment(Alignment::Right)
            ->modalWidth('xl2')
            ->after(function($record){
                $fol = DB::table('seriesfacs')->where('id',$record['serie'])->get();
                $nfol = $fol[0]->folio + 1;
                DB::table('seriesfacs')->where('id',$record['serie'])->update([
                    'folio'=>$nfol
                ]);
                $cli = DB::table('terceros')->where('id',$record['cve_clie'])->get();
                $partidas = $record['Partidas'];
                foreach($partidas as $partida)
                {
                    $pr = DB::table('productos')->where('id',$partida['id_prod'])->get();
                    $nex = $pr[0]->existencia + $partida['cant'];
                    $cosp = $partida['precio'];
                    if($pr[0]->costo_p > 0)
                    {
                        $cosp = ($pr[0]->costo_p + $partida['precio']) / 2;
                    }
                    DB::table('productos')->where('id',$partida['id_prod'])->update([
                        'existencia'=>$nex,
                        'costo_u'=>$partida['precio'],
                        'costo_p'=>$cosp
                    ]);

                    $mov =  DB::table('movinventarios')->select(DB::raw('MAX(folio) folio'))->get();
                    $nfol = 1;
                    if($mov[0]->folio){
                        $nfol = $mov[0]->folio + 1;
                    }
                    //dd($nfol);
                    $mov = DB::table('movinventarios')->insertGetId([
                        'folio'=>$nfol,'fecha'=>Carbon::now(),'tipo'=>'Compra',
                        'producto'=>$partida['id_prod'],'descripcion'=>$pr[0]->descripcion,
                        'concepto'=>$record['clave_doc'],'tipoter'=>'Proveedor',
                        'idter'=>$record['cve_clie'],'nomter'=>$cli[0]->nombre,'cant'=>$partida['cant'],
                        'costou'=>$partida['precio'],'costot'=>$partida['subtotal'],
                        'preciou'=>0,'preciot'=>0,'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('movinventarios_team')->insert([
                        'movinventarios_id'=>$mov,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                }
            $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PG')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
            $poliza = CatPolizas::create([
                'tipo'=>'PG',
                'folio'=>$nopoliza,
                'fecha'=>Carbon::now(),
                'concepto'=>$cli[0]->nombre,
                'cargos'=>$record['total'],
                'abonos'=>$record['total'],
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>$record['clave_doc'],
                'uuid'=>'',
                'tiposat'=>'Dr',
                'team_id'=>Filament::getTenant()->id
            ]);
                $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$cli[0]->cuenta,
                    'cuenta'=>$cli[0]->nombre,
                    'concepto'=>$cli[0]->nombre,
                    'cargo'=>0,
                    'abono'=>$record['total'],
                    'factura'=>$record['clave_doc'],
                    'nopartida'=>1,
                    'uuid'=>'',
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'11502000',
                    'cuenta'=>'Materia prima y materiales',
                    'concepto'=>$cli[0]->nombre,
                    'cargo'=>$record['subtotal'],
                    'abono'=>0,
                    'factura'=>$record['clave_doc'],
                    'nopartida'=>2,
                    'uuid'=>'',
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'11901000',
                    'cuenta'=>'IVA trasladado no cobrado',
                    'concepto'=>$cli[0]->nombre,
                    'cargo'=>$record['traslados'],
                    'abono'=>0,
                    'factura'=>$record['clave_doc'],
                    'nopartida'=>3,
                    'uuid'=>'',
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
