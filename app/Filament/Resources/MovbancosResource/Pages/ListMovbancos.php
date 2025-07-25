<?php

namespace App\Filament\Resources\MovbancosResource\Pages;

use App\Filament\Resources\MovbancosResource;
use App\Models\BancoCuentas;
use App\Models\Movbancos;
use App\Models\Saldosbanco;
use Filament\Actions;
use Filament\Actions\Action as ActionsAction;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
    public ?float $saldo_cuenta = 0;
    public ?float $saldo_cuenta_ant = 0;
    public ?float $saldo_cuenta_act = 0;
    public static ?array $selected_records = [];
    public ?int $recordid;

    public ?int $selected_tier;

    protected function getTableHeading(): string
    {
        // Get the current tab from the request
        $currentUrl = request()->url();
        $urlParts = explode('/', $currentUrl);
        $tabSlug = end($urlParts);

        // If we have a valid tab slug
        if ($tabSlug && $tabSlug !== 'movbancos') {
            $cuentaId = null;
            $tiers = BancoCuentas::orderBy('id', 'asc')->get();

            foreach ($tiers as $tier) {
                $name = $tier->banco;
                $slug = str($name)->slug()->toString();

                if ($slug === $tabSlug) {
                    $cuentaId = $tier->id;
                    break;
                }
            }

            if ($cuentaId) {
                $ejercicio = Filament::getTenant()->ejercicio;
                $periodo = Filament::getTenant()->periodo;

                $saldo = Saldosbanco::where('cuenta', $cuentaId)
                    ->where('ejercicio', $ejercicio)
                    ->where('periodo', $periodo)
                    ->first();

                if ($saldo) {
                    $this->saldo_cuenta = $saldo->inicial;
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    $formattedBalance = $formatter->formatCurrency($saldo->inicial, 'MXN');

                    return "Movimientos Bancarios - Saldo Inicial: {$formattedBalance}";
                }
            }
        }

        return "Movimientos Bancarios";
    }

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
                    //dd($excelImportAction);
                    $tax_id = $data['tax_id'];
                    $team_id = $data['team_id'];
                    $contabilizada = $data['contabilizada'];
                    $cuenta = $data['cuenta'];
                    //$pendiente = $data['importe'];
                    $excelImportAction->additionalData([
                        'tax_id' => $tax_id,
                        'team_id' => $team_id,
                        'contabilizada' => $contabilizada,
                        'cuenta' => $cuenta,

                    ]);
                })->validateUsing([
                    'importe' => 'required|numeric',
                ])
                ->mutateAfterValidationUsing(
                    closure: function(array $data): array{
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
                            $val_sdos = DB::table('saldosbancos')
                                ->where('cuenta',$data['cuenta'])
                                ->where('ejercicio',$ejer)->get();
                            if(count($val_sdos) == 0){
                                for($i=1;$i<13;$i++) {
                                    DB::table('saldosbancos')->insert([
                                        'cuenta' => $data['cuenta'],
                                        'inicial' => 0.00,
                                        'ingresos' => 0.00,
                                        'egresos' => 0.00,
                                        'actual' => 0.00,
                                        'ejercicio' => $ejer,
                                        'periodo' => $i
                                    ]);
                                }
                            }
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
                            $emp_a = Filament::getTenant()->id;
                            DB::statement("UPDATE movbancos SET pendiente_apli = importe
                            WHERE pendiente_apli = 0 AND contabilizada = 'NO' AND periodo = $peri AND ejercicio = $ejer AND team_id = $emp_a");
                            //------------------------------------------------------
                        }
                    }
                    //dd(count($data_array));
                }),
            Actions\CreateAction::make()
                ->label('Agregar')
                ->icon('fas-plus')
                ->createAnother(false)
                ->after(function ($record){
                    $id = $record->id;
                    $importe = $record->importe;
                    Movbancos::where('id',$id)->update([
                        'pendiente_apli'=>$importe
                    ]);
                })
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
        $this->selected_tier = $tier->id;
        return $tabs;
    }
}
