<?php

namespace App\Filament\Resources\FacturasResource\Pages;

use App\Filament\Resources\FacturasResource;
use App\Http\Controllers\TimbradoController;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\Facturas;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ListFacturas extends ListRecords
{
    protected static string $resource = FacturasResource::class;

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
            ->modalWidth('xl2')->after(function($record){
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
                    $nex = $pr[0]->existencia - $partida['cant'];
                    DB::table('productos')->where('id',$partida['id_prod'])->update([
                        'existencia'=>$nex
                    ]);

                    $mov =  DB::table('movinventarios')->select(DB::raw('MAX(folio) folio'))->get();
                    $nfol = 1;
                    if($mov[0]->folio){
                        $nfol = $mov[0]->folio + 1;
                    }
                    //dd($nfol);
                    $mov = DB::table('movinventarios')->insertGetId([
                        'folio'=>$nfol,'fecha'=>Carbon::now(),'tipo'=>'Factura',
                        'producto'=>$partida['id_prod'],'descripcion'=>$pr[0]->descripcion,
                        'concepto'=>$record['clave_doc'],'tipoter'=>'Cliente',
                        'idter'=>$record['cve_clie'],'nomter'=>$cli[0]->nombre,'cant'=>$partida['cant'],
                        'preciou'=>$partida['precio'],'preciot'=>$partida['subtotal'],
                        'costou'=>$pr[0]->costo_p,'costot'=>($pr[0]->costo_u * $partida['cant']),'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('movinventarios_team')->insert([
                        'movinventarios_id'=>$mov,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                }
            //--------------------------------------------------------------
            $tipodoc = 'F';
            $factura = $record->getKey();
            $emisor = Filament::getTenant()->id;
            $receptor =$record['cve_clie'];
            $timbrado = new TimbradoController;
            $restimb = $timbrado->TimbrarFactura($factura,$emisor,$receptor,$tipodoc);
            $resultado = json_decode($restimb);
            $cod = $resultado->codigo;
            $uuid = '';
            $imp_fa = 'NO';
            $xml_con = '';
            if($cod == 200)
            {
                Notification::make()
                ->title('Factura Timbrada Correctamente')
                ->success()
                ->send();
                $xml_con = $resultado->cfdi;
                $uuid = $timbrado->actualiza_fac_tim($factura,$resultado->cfdi,'F');
                $pdf_file = app(TimbradoController::class)->genera_pdf($resultado->cfdi);
                $facturamodel = Facturas::find($factura);
                $facturamodel->pdf_file = $pdf_file;
                $facturamodel->xml = $xml_con;
                $facturamodel->save();
                $imp_fa = 'SI';
            }
            //--------------------------------------------------------------
            $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','PV')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
            $poliza = CatPolizas::create([
                'tipo'=>'PV',
                'folio'=>$nopoliza,
                'fecha'=>Carbon::now(),
                'concepto'=>$cli[0]->nombre,
                'cargos'=>$record['total'],
                'abonos'=>$record['total'],
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>$record['clave_doc'],
                'uuid'=>$uuid,
                'tiposat'=>'Dr',
                'team_id'=>Filament::getTenant()->id
            ]);
                $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$cli[0]->cuenta,
                    'cuenta'=>$cli[0]->nombre,
                    'concepto'=>$cli[0]->nombre,
                    'cargo'=>$record['total'],
                    'abono'=>0,
                    'factura'=>$record['clave_doc'],
                    'nopartida'=>1,
                    'uuid'=>$uuid,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'40101000',
                    'cuenta'=>'Ventas',
                    'concepto'=>$cli[0]->nombre,
                    'cargo'=>0,
                    'abono'=>$record['subtotal'],
                    'factura'=>$record['clave_doc'],
                    'nopartida'=>2,
                    'uuid'=>$uuid,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'20901000',
                    'cuenta'=>'IVA trasladado no cobrado',
                    'concepto'=>$cli[0]->nombre,
                    'cargo'=>0,
                    'abono'=>$record['traslados'],
                    'factura'=>$record['clave_doc'],
                    'nopartida'=>3,
                    'uuid'=>$uuid,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                if($imp_fa == 'SI')
                {
                    if (file_exists($uuid.'.pdf')) {
                        unlink($uuid.'.pdf');
                    }
                    file_put_contents($uuid.'.pdf',base64_decode($pdf_file));
                    if (file_exists($uuid.'.xml')) {
                        unlink($uuid.'.xml');
                    }
                    file_put_contents($uuid.'.xml',$xml_con);
                    $arch_zip =$uuid.'.zip';
                    if (file_exists($uuid.'.zip')) {
                        unlink($uuid.'.zip');
                    }
                    $zip = new ZipArchive();
                    if ($zip->open($arch_zip, ZipArchive::CREATE) != true) {
                        die ("Could not open archive");
                    }
                    $zip->addFile($uuid.'.pdf',$uuid.'.pdf');
                    $zip->addFile($uuid.'.xml',$uuid.'.xml');
                    $zip->close();
                    return response()->download($arch_zip);

                }
            }),
        ];
    }
}
