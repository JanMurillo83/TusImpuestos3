<?php

namespace App\Filament\Resources\MovbancosResource\Pages;

use App\Filament\Resources\MovbancosResource;
use App\Models\BancoCuentas;
use App\Models\Saldosbanco;
use Filament\Actions;
use Filament\Actions\Action as ActionsAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\DB;

class ListMovbancos extends ListRecords
{
    protected static string $resource = MovbancosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Importar')
                ->color("primary")
                ->beforeUploadField([
                    Hidden::make('tax_id')
                        ->default(Filament::getTenant()->taxid),
                    Hidden::make('team_id')
                        ->default(Filament::getTenant()->id),
                    Hidden::make('ejercicio')
                        ->default(Filament::getTenant()->ejercicio),
                    Hidden::make('periodo')
                        ->default(Filament::getTenant()->periodo),
                    Hidden::make('contabilizada')
                        ->default('NO'),
                    Select::make('cuenta')
                        ->label('Cuenta Bancaria')
                        ->required()
                        ->options(BancoCuentas::where('team_id',Filament::getTenant()->id)->pluck('banco','id'))
                ])
                ->sampleExcel(
                    sampleData: [
                        ['fecha' => '2024-01-01', 'Tipo' => 'E', 'importe' => '1000.00', 'concepto' => 'Ejemplo Entrada', 'ejercicio' => 2024, 'periodo' => 1],
                        ['fecha' => '2024-01-01', 'Tipo' => 'S', 'importe' => '1000.00', 'concepto' => 'Ejemplo Salida', 'ejercicio' => 2024, 'periodo' => 1],
                    ],
                    fileName: 'ImportaMovBanco.xlsx',
                    sampleButtonLabel: 'Descargar Layout',
                    customiseActionUsing: fn(Action $action) => $action->color('gray')
                        ->icon('heroicon-m-clipboard')
                        ->requiresConfirmation(),
                )->beforeImport(function (array $data, $livewire, $excelImportAction) {
                    $tax_id = $data['tax_id'];
                    $team_id = $data['team_id'];
                    $contabilizada = $data['contabilizada'];
                    $cuenta = $data['cuenta'];
                    $excelImportAction->additionalData([
                        'tax_id' => $tax_id,
                        'team_id' => $team_id,
                        'contabilizada' => $contabilizada,
                        'cuenta' => $cuenta
                    ]);
                })->validateUsing([
                    'importe' => 'required|numeric',
                ])
                ->mutateAfterValidationUsing(
                    closure: function(array $data): array{
                        dd($data);
                        $tip = $data['tipo'];
                        $sdos = DB::table('saldosbancos')
                        ->where('cuenta',$data['cuenta'])
                        ->where('ejercicio',$data['ejercicio'])
                        ->where('periodo',$data['periodo'])->get();
                        $inicia = $sdos[0]->inicial;
                        $ingre = $sdos[0]->ingre + $data['importe'];
                        $salid = $sdos[0]->salid + $data['importe'];
                        if($tip == 'E'){
                            DB::table('saldosbancos')
                            ->where('cuenta',$data['cuenta'])
                            ->where('ejercicio',$data['ejercicio'])
                            ->where('periodo',$data['periodo'])->update([
                                'ingresos'=>$ingre
                            ]);
                        }
                        else{
                            DB::table('saldosbancos')
                            ->where('cuenta',$data['cuenta'])
                            ->where('ejercicio',$data['ejercicio'])
                            ->where('periodo',$data['periodo'])->update([
                                'egresos'=>$salid
                            ]);
                        }
                        $sdos = DB::table('saldosbancos')
                        ->where('cuenta',$data['cuenta'])
                        ->where('ejercicio',$data['ejercicio'])
                        ->where('periodo',$data['periodo'])->get();
                        $inicia = $sdos[0]->inicial;
                        $ingre = $sdos[0]->ingre;
                        $salid = $sdos[0]->salid;
                        $term = $inicia + $ingre - $salid;
                        DB::table('saldosbancos')
                        ->where('cuenta',$data['cuenta'])
                        ->where('ejercicio',$data['ejercicio'])
                        ->where('periodo',$data['periodo'])->update([
                            'actual'=>$term
                        ]);
                        return $data;
                    },
                )->afterImport(closure: function(array $data){
                    $filen = $data['upload']->getFilename();
                    $fileName = storage_path('app/livewire-tmp/'.$filen);
                    $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fileName);
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
                    $reader->setReadEmptyCells(true);
                    $spreadsheet = $reader->load($fileName);
                    $spreadsheet = $spreadsheet->getActiveSheet();
                    $data_array =  $spreadsheet->toArray();
                    $arrs = count($data_array);
                    for($i=0;$i<$arrs;$i++)
                    {
                        if($i > 0)
                        {
                            $tipo = $data_array[$i][1];
                            $importe = $data_array[$i][2];
                            $peri = $data_array[$i][5];
                            $ejer = $data_array[$i][4];
                            //------------------------------------------------------
                            $sdos = DB::table('saldosbancos')
                                ->where('cuenta',$data['cuenta'])
                                ->where('ejercicio',$ejer)
                                ->where('periodo',$peri)->get();
                                //dd($sdos);
                            $inicia = $sdos[0]->inicial;
                            $ingre = $sdos[0]->ingresos + $importe;
                            $salid = $sdos[0]->egresos + $importe;
                            if($tipo == 'E'){
                                DB::table('saldosbancos')
                                ->where('cuenta',$data['cuenta'])
                                ->where('ejercicio',$ejer)
                                ->where('periodo',$peri)->update([
                                    'ingresos'=>$ingre
                                ]);
                            }
                            else{
                                DB::table('saldosbancos')
                                ->where('cuenta',$data['cuenta'])
                                ->where('ejercicio',$ejer)
                                ->where('periodo',$peri)->update([
                                    'egresos'=>$salid
                                ]);
                            }
                            $sdos = DB::table('saldosbancos')
                            ->where('cuenta',$data['cuenta'])
                            ->where('ejercicio',$ejer)
                            ->where('periodo',$peri)->get();
                            $inicia = $sdos[0]->inicial;
                            $ingre = $sdos[0]->ingresos;
                            $salid = $sdos[0]->egresos;
                            $term = $inicia + $ingre - $salid;
                            DB::table('saldosbancos')
                            ->where('cuenta',$data['cuenta'])
                            ->where('ejercicio',$ejer)
                            ->where('periodo',$peri)->update([
                                'actual'=>$term
                            ]);
                            //------------------------------------------------------
                        }
                    }
                    //dd(count($data_array));
                }),
            Actions\CreateAction::make()
                ->label('Agregar')
                ->icon('fas-plus')
                ->createAnother(false)
                /*ActionsAction::make('Iniciales')
                ->form([
                    TextInput::make('inicial')
                    ->default(0),
                    TextInput::make('ejercicio')
                    ->default(2024),
                ])->action(function($data){
                    $ban = 1;
                    $bandata = [
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>1],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>2],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>3],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>4],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>5],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>6],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>7],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>8],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>9],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>10],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>11],
                        ['cuenta'=>$ban,'inicial'=>$data['inicial'],'ingresos'=>0,'egresos'=>0,'actual'=>$data['inicial'],'ejercicio'=>$data['ejercicio'],'periodo'=>12]];
                    Saldosbanco::insert($bandata);
                })*/
        ];
    }

    public function getTabs(): array
    {
        //$tabs = ['all' => Tab::make('All')->badge($this->getModel()::count())];
        $tabs = [];
        $tiers = BancoCuentas::orderBy('id', 'asc')->get();

        foreach ($tiers as $tier) {
            $name = $tier->banco;
            $slug = str($name)->slug()->toString();

            $tabs[$slug] = Tab::make($name)
                ->modifyQueryUsing(function ($query) use ($tier) {
                    return $query->where('cuenta', $tier->id);
                });
        }

        return $tabs;
    }
}

