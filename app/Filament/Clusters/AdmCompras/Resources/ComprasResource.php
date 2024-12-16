<?php

namespace App\Filament\Clusters\AdmCompras\Resources;

use App\Filament\Clusters\AdmCompras;
use App\Filament\Clusters\AdmCompras\Resources\ComprasResource\Pages;
use App\Filament\Clusters\AdmCompras\Resources\OrdenesResource\RelationManagers;
use App\Models\Compras;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Movinventario;
use App\Models\Ordenes;
use App\Models\OrdenesPartidas;
use App\Models\Proveedores;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class ComprasResource extends Resource
{
    protected static ?string $model = Compras::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'fas-cart-plus';
    protected static ?string $label = 'Compra';
    protected static ?string $pluralLabel = 'Compras';

    protected static ?string $cluster = AdmCompras::class;

    public static function form(Form $form): Form
    {
        return $form
        ->columns(6)
        ->schema([
            Hidden::make('team_id')->default(Filament::getTenant()->id),
            Split::make([
                FieldSet::make('Compra')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Hidden::make('orden'),
                        Forms\Components\TextInput::make('folio')
                        ->required()
                        ->numeric()
                        ->readOnly()
                        ->default(function(){
                            return count(Compras::all()) + 1;
                        }),
                    Forms\Components\Select::make('prov')
                        ->searchable()
                        ->label('Proveedor')
                        ->columnSpan(2)
                        ->live()
                        ->required()
                        ->options(Proveedores::all()->pluck('nombre','id'))
                        ->afterStateUpdated(function(Get $get,Set $set){
                            $prov = Proveedores::where('id',$get('prov'))->get();
                            if(count($prov) > 0){
                            $prov = $prov[0];
                            $set('nombre',$prov->nombre);
                            }
                        })->disabledOn('edit'),
                    Forms\Components\DatePicker::make('fecha')
                        ->required()
                        ->default(Carbon::now())->disabledOn('edit'),
                    Forms\Components\Select::make('esquema')
                        ->options(Esquemasimp::all()->pluck('descripcion','id'))
                        ->default(1)->disabledOn('edit'),
                    Forms\Components\Textarea::make('observa')
                        ->columnSpan(4)->label('Observaciones')
                        ->rows(1),
                    TableRepeater::make('partidas')
                        ->relationship()
                        ->addActionLabel('Agregar')
                        ->headers([
                            Header::make('Cantidad'),
                            Header::make('Item'),
                            Header::make('Descripcion')->width('200px'),
                            Header::make('Unitario'),
                            Header::make('Subtotal'),
                        ])->schema([
                            TextInput::make('cant')->numeric()->default(1)->label('Cantidad')
                            ->live()
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $cant = $get('cant');
                                $cost = $get('costo');
                                $subt = $cost * $cant;
                                $set('subtotal',$subt);
                                $ivap = $get('../../esquema');
                                $esq = Esquemasimp::where('id',$ivap)->get();
                                $esq = $esq[0];
                                $set('iva',$subt * ($esq->iva*0.01));
                                $set('retiva',$subt * ($esq->retiva*0.01));
                                $set('retisr',$subt * ($esq->retisr*0.01));
                                $set('ieps',$subt * ($esq->ieps*0.01));
                                $ivapar = $subt * ($esq->iva*0.01);
                                $retivapar = $subt * ($esq->iva*0.01);
                                $retisrpar = $subt * ($esq->iva*0.01);
                                $iepspar = $subt * ($esq->iva*0.01);
                                $tot = $subt + $ivapar - $retivapar - $retisrpar + $iepspar;
                                $set('total',$tot);
                                $set('prov',$get('../../prov'));
                                Self::updateTotals($get,$set);
                            }),
                            TextInput::make('item')
                                ->live(onBlur:true)
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $prod = Inventario::where('id',$get('item'))->get();
                                    $prod = $prod[0];
                                    $set('descripcion',$prod->descripcion);
                                    $set('costo',$prod->u_costo);
                                })->suffixAction(
                                    Action::make('AbreItem')
                                    ->icon('fas-circle-question')
                                    ->form([
                                        Select::make('SelItem')
                                        ->label('Seleccionar')
                                        ->searchable()
                                        ->options(Inventario::all()->pluck('descripcion','id'))
                                    ])
                                    ->action(function(Set $set,Get $get,$data){
                                        $cant = $get('cant');
                                        $item = $data['SelItem'];
                                        $set('item',$item);
                                        $prod = Inventario::where('id',$item)->get();
                                        $prod = $prod[0];
                                        $set('descripcion',$prod->descripcion);
                                        $set('costo',$prod->u_costo);
                                        $subt = $prod->u_costo * $cant;
                                        $set('subtotal',$subt);
                                        $ivap = $get('../../esquema');
                                        $esq = Esquemasimp::where('id',$ivap)->get();
                                        $esq = $esq[0];
                                        $set('iva',$subt * ($esq->iva*0.01));
                                        $set('retiva',$subt * ($esq->retiva*0.01));
                                        $set('retisr',$subt * ($esq->retisr*0.01));
                                        $set('ieps',$subt * ($esq->ieps*0.01));
                                        $ivapar = $subt * ($esq->iva*0.01);
                                        $retivapar = $subt * ($esq->iva*0.01);
                                        $retisrpar = $subt * ($esq->iva*0.01);
                                        $iepspar = $subt * ($esq->iva*0.01);
                                        $tot = $subt + $ivapar - $retivapar - $retisrpar + $iepspar;
                                        $set('total',$tot);
                                        $set('prov',$get('../../prov'));
                                        Self::updateTotals($get,$set);
                                    })
                            ),
                            TextInput::make('descripcion'),
                            TextInput::make('costo')
                                ->numeric()
                                ->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2)
                                ->live()
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $cant = $get('cant');
                                    $cost = $get('costo');
                                    $subt = $cost * $cant;
                                    $set('subtotal',$subt);
                                    $ivap = $get('../../esquema');
                                    $esq = Esquemasimp::where('id',$ivap)->get();
                                    $esq = $esq[0];
                                    $ivapar = $subt * ($esq->iva*0.01);
                                    $retivapar = $subt * ($esq->retiva*0.01);
                                    $retisrpar = $subt * ($esq->retisr*0.01);
                                    $iepspar = $subt * ($esq->ieps*0.01);
                                    $set('iva',$ivapar);
                                    $set('retiva',$retivapar);
                                    $set('retisr',$retisrpar);
                                    $set('ieps',$iepspar);
                                    $tot = $subt + $ivapar - $retivapar - $retisrpar + $iepspar;
                                    $set('total',$tot);
                                    $set('prov',$get('../../prov'));
                                    Self::updateTotals($get,$set);
                                }),
                            TextInput::make('subtotal')
                                ->numeric()
                                ->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                            Hidden::make('iva'),
                            Hidden::make('retiva'),
                            Hidden::make('retisr'),
                            Hidden::make('ieps'),
                            Hidden::make('total'),
                            Hidden::make('unidad'),
                            Hidden::make('cvesat'),
                            Hidden::make('prov'),
                            Hidden::make('observa'),
                            Hidden::make('idorden'),
                            Hidden::make('team_id')->default(Filament::getTenant()->id),
                        ])->columnSpan('full')->streamlined()

                    ])->grow(true)->columns(5),
                Section::make('Totales')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                        ->readOnly()
                        ->numeric()->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                    Forms\Components\TextInput::make('Impuestos')
                        ->readOnly()
                        ->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                    Forms\Components\Hidden::make('iva'),
                    Forms\Components\Hidden::make('retiva'),
                    Forms\Components\Hidden::make('retisr'),
                    Forms\Components\Hidden::make('ieps'),
                    Forms\Components\TextInput::make('total')
                        ->numeric()
                        ->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                    Actions::make([
                        Action::make('ImportarExcel')
                            ->visible(function(Get $get){
                                if($get('prov') > 0&&$get('subtotal') == 0) return true;
                                else return false;
                            })
                            ->label('Importar Partidas')
                            ->badge()->tooltip('Importar Excel')
                            ->modalCancelActionLabel('Cancelar')
                            ->modalSubmitActionLabel('Importar')
                            ->icon('fas-file-excel')
                            ->form([
                                FileUpload::make('ExcelFile')
                                ->label('Archivo Excel')
                                ->storeFiles(false)
                                ])->action(function(Get $get,Set $set,$data){
                                    //dd($data['ExcelFile']->path());
                                    $archivo = $data['ExcelFile']->path();
                                    $tipo=IOFactory::identify($archivo);
                                    $lector=IOFactory::createReader($tipo);
                                    $libro = $lector->load($archivo, IReader::IGNORE_EMPTY_CELLS);
                                    $hoja = $libro->getActiveSheet();
                                    $rows = $hoja->toArray();
                                    $r = 0;
                                    $partidas = [];
                                    foreach($rows as $row)
                                    {
                                        if($r > 0)
                                        {
                                            $cant = $row[0];
                                            $item = $row[1];
                                            $cost = $row[2];
                                            $prod = Inventario::where('clave',$item)->get();
                                            $prod = $prod[0];
                                            $subt = $cost * $cant;
                                            $ivap = $get('esquema');
                                            $esq = Esquemasimp::where('id',$ivap)->get();
                                            $esq = $esq[0];
                                            $ivapar = $subt * ($esq->iva*0.01);
                                            $retivapar = $subt * ($esq->retiva*0.01);
                                            $retisrpar = $subt * ($esq->retisr*0.01);
                                            $iepspar = $subt * ($esq->ieps*0.01);
                                            $tot = $subt + $ivapar - $retivapar - $retisrpar + $iepspar;
                                            $data = ['cant'=>$cant,'item'=>$prod->id,'descripcion'=>$prod->descripcion,
                                            'costo'=>$cost,'subtotal'=>$subt,'iva'=>$ivapar,
                                            'retiva'=>$retivapar,'retisr'=>$retisrpar,
                                            'ieps'=>$iepspar,'total'=>$tot,'prov'=>$get('prov')];
                                            array_push($partidas,$data);
                                        }
                                        $r++;
                                    }
                                $set('partidas', $partidas);
                                Self::updateTotals2($get,$set);
                            })
                        ]),
                        Actions::make([
                        Action::make('Imprimir Compra')
                            ->badge()->tooltip('Imprimir Compra')
                            ->icon('fas-print')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitAction('')
                            ->modalContent(function(Get $get){
                                $idorden = $get('id');
                                if($idorden != null)
                                {
                                    $archivo = public_path('/Reportes/RecCompra.pdf');
                                    if(File::exists($archivo)) unlink($archivo);
                                    SnappyPdf::loadView('RecepcionCompra',['idorden'=>$idorden])
                                        ->setOption("footer-right", "Pagina [page] de [topage]")
                                        ->setOption('encoding', 'utf-8')
                                        ->save($archivo);
                                    $ruta = env('APP_URL').'/Reportes/RecCompra.pdf';
                                    //dd($ruta);
                                }
                            })->form([
                                PdfViewerField::make('archivo')
                                ->fileUrl(env('APP_URL').'/Reportes/RecCompra.pdf')
                            ])
                    ])->visibleOn('view'),
                    Actions::make([
                        Action::make('Enlazar Orden')
                            ->badge()->tooltip('Enlazar Orden de Compra')
                            ->icon('fas-file-import')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitActionLabel('Seleccionar')
                            ->form([
                                Select::make('OrdenC')
                                ->searchable()
                                ->label('Seleccionar Orden de Compra')
                                ->options(
                                    Ordenes::whereIn('estado',['Activa','Parcial'])
                                    ->select(DB::raw("concat('Folio: ',folio,' Fecha: ',fecha,' Proveedor: ',nombre,' Importe: ',total) as Orden"),'id')
                                    ->pluck('Orden','id'))
                            ])->action(function(Get $get,Set $set,$data){
                                $selorden = $data['OrdenC'];
                                $set('orden',$selorden);
                                $orden = Ordenes::where('id',$data['OrdenC'])->get();
                                $Opartidas = OrdenesPartidas::where('ordenes_id',$data['OrdenC'])->get();
                                $orden = $orden[0];
                                $set('prov',$orden->prov);
                                $set('nombre',$orden->nombre);
                                $set('observa',$orden->observa);
                                $partidas = [];
                                foreach($Opartidas as $opar)
                                {
                                    $data = ['cant'=>$opar->cant,'item'=>$opar->item,'descripcion'=>$opar->descripcion,
                                            'costo'=>$opar->costo,'subtotal'=>$opar->subtotal,'iva'=>$opar->iva,
                                            'retiva'=>$opar->retiva,'retisr'=>$opar->retisr,
                                            'ieps'=>$opar->ieps,'total'=>$opar->total,'prov'=>$orden->prov,'idorden'=>$selorden];
                                    array_push($partidas,$data);
                                }
                                $set('partidas', $partidas);
                                Self::updateTotals2($get,$set);
                            })
                    ])
                    ])->grow(false),

            ])->columnSpanFull(),
            Forms\Components\Hidden::make('nombre'),
            Forms\Components\Hidden::make('estado')->default('Activa'),
        ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $subtotal = collect($get('../../partidas'))->pluck('subtotal')->sum();
        $impuesto1 = collect($get('../../partidas'))->pluck('iva')->sum();
        $impuesto2 = collect($get('../../partidas'))->pluck('retiva')->sum();
        $impuesto3 = collect($get('../../partidas'))->pluck('retisr')->sum();
        $impuesto4 = collect($get('../../partidas'))->pluck('ieps')->sum();
        $total = collect($get('../../partidas'))->pluck('total')->sum();
        $set('../../subtotal',$subtotal);
        $set('../../iva',$impuesto1);
        $set('../../retiva',$impuesto2);
        $set('../../retisr',$impuesto3);
        $set('../../ieps',$impuesto4);
        $traslados = floatval($impuesto1) + floatval($impuesto4);
        $retenciones = floatval($impuesto2) + floatval($impuesto3);
        $set('../../Impuestos',$traslados-$retenciones);
        $set('../../total',$total);
    }

    public static function updateTotals2(Get $get, Set $set): void
    {
        $subtotal = collect($get('partidas'))->pluck('subtotal')->sum();
        $impuesto1 = collect($get('partidas'))->pluck('iva')->sum();
        $impuesto2 = collect($get('partidas'))->pluck('retiva')->sum();
        $impuesto3 = collect($get('partidas'))->pluck('retisr')->sum();
        $impuesto4 = collect($get('partidas'))->pluck('ieps')->sum();
        $total = collect($get('partidas'))->pluck('total')->sum();
        $set('subtotal',$subtotal);
        $set('iva',$impuesto1);
        $set('retiva',$impuesto2);
        $set('retisr',$impuesto3);
        $set('ieps',$impuesto4);
        $traslados = floatval($impuesto1) + floatval($impuesto4);
        $retenciones = floatval($impuesto2) + floatval($impuesto3);
        $set('Impuestos',$traslados-$retenciones);
        $set('total',$total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->label('Proveedor'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable()
                    ->currency('USD',true),
                Tables\Columns\TextColumn::make('iva')
                    ->numeric()
                    ->sortable()
                    ->currency('USD',true),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable()
                    ->currency('USD',true),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                ->label('')->icon(null)
                //->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                //->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('7xl'),
                ActionsAction::make('Cancelar')
                ->icon('fas-ban')
                ->label('Cancelar')
                ->color(Color::Red)
                ->requiresConfirmation()
                ->action(function(Model $record){
                    $est = $record->estado;
                    if($est == 'Activa')
                    {
                        Compras::where('id',$record->id)->update([
                            'estado'=>'Cancelada'
                        ]);
                        Notification::make()
                        ->title('Compra Cancelada')
                        ->success()
                        ->send();
                    }
                })
            ])
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nuevo Cliente')
                ->label('Agregar')->icon('fas-circle-plus')->badge()
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('7xl')->button()
                ->after(function($record){
                    $partidas = $record->partidas;
                    $nopar = 0;
                    foreach($partidas as $partida)
                    {
                        $arti = $partida->item;
                        $inve = Inventario::where('id',$arti)->get();
                        $inve = $inve[0];
                        if($inve->servicio == 'NO')
                        {
                            Movinventario::insert([
                                'producto'=>$partida->item,
                                'tipo'=>'Entrada',
                                'fecha'=>Carbon::now(),
                                'cant'=>$partida->cant,
                                'costo'=>$partida->costo,
                                'precio'=>0,
                                'concepto'=>1,
                                'tipoter'=>'P',
                                'tercero'=>$record->prov
                            ]);

                            $cost = $partida->costo;
                            $cant = $inve->exist + $partida->cant;
                            $avg = $inve->p_costo * $inve->exist;
                            $avgp = 0;
                            if($avg == 0) $avgp = $cost;
                            else $avgp = (($inve->p_costo + $cost) * ($inve->exist + $cant)) / ($inve->exist + $cant);
                            Inventario::where('id',$arti)->update([
                                'exist' => $cant,
                                'u_costo'=>$cost,
                                'p_costo'=>$avgp
                            ]);
                        }
                        OrdenesPartidas::where(['ordenes_id'=>$partida->idorden,'item'=>$partida->item])->update([
                            'idcompra'=>$record->folio
                        ]);
                        $nopar++;
                    }
                    $opo = OrdenesPartidas::where(['ordenes_id'=>$partida->idorden,'item'=>$partida->item])->get();
                    $noparor = count($opo);
                    $estado = '';
                    if($nopar == $noparor) $estado = 'Enlazada';
                    else $estado = 'Parcial';
                    Ordenes::where('id',$record->orden)->update([
                        'estado'=>$estado,
                        'compra'=>$record->folio
                    ]);
                })
            ],HeaderActionsPosition::Bottom)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompras::route('/'),
            //'create' => Pages\CreateCompras::route('/create'),
            //'edit' => Pages\EditCompras::route('/{record}/edit'),
        ];
    }
}

