<?php

namespace App\Filament\Clusters\AdmVentas\Resources;

use App\Filament\Clusters\AdmVentas;
use App\Filament\Clusters\AdmVentas\Resources\CotizacionesResource\Pages;
use App\Filament\Clusters\AdmVentas\Resources\CotizacionesResource\RelationManagers;
use App\Models\Cotizaciones;
use App\Models\Clientes;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as ActionsAction;
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
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class CotizacionesResource extends Resource
{
    protected static ?string $model = Cotizaciones::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'fas-file-invoice';
    protected static ?string $label = 'Cotizacion';
    protected static ?string $pluralLabel = 'Cotizaciones';
    protected static ?string $cluster = AdmVentas::class;

    public static function form(Form $form): Form
    {
        return $form
        ->columns(6)
        ->schema([
            Split::make([
                Fieldset::make('Cotizacion')
                    ->schema([
                        Hidden::make('team_id')->default(Filament::getTenant()->id),
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Hidden::make('serie')->default('C'),
                        Forms\Components\Hidden::make('folio')
                        ->default(function(){
                            return count(Cotizaciones::where('team_id',Filament::getTenant()->id)->get()) + 1;
                        }),
                        Forms\Components\TextInput::make('docto')
                        ->label('Documento')
                        ->required()
                        ->readOnly()
                        ->default(function(){
                            $fol = count(Cotizaciones::where('team_id',Filament::getTenant()->id)->get()) + 1;
                            return 'C'.$fol;
                        }),
                    Forms\Components\Select::make('clie')
                        ->searchable()
                        ->label('Cliente')
                        ->columnSpan(2)
                        ->live()
                        ->required()
                        ->options(Clientes::all()->pluck('nombre','id'))
                        ->afterStateUpdated(function(Get $get,Set $set){
                            $prov = Clientes::where('id',$get('clie'))->get();
                            if(count($prov) > 0){
                            $prov = $prov[0];
                            $set('nombre',$prov->nombre);
                            }
                        })->disabledOn('edit'),
                    Forms\Components\DatePicker::make('fecha')
                        ->required()
                        ->default(Carbon::now())->disabledOn('edit'),
                    Forms\Components\Select::make('esquema')
                        ->options(Esquemasimp::where('team_id',Filament::getTenant()->id)->pluck('descripcion','id'))
                        ->default(Esquemasimp::where('team_id',Filament::getTenant()->id)->first()->id)->disabledOn('edit'),
                    Forms\Components\Textarea::make('observa')
                        ->columnSpan(3)->label('Observaciones')
                        ->rows(1),
                    Forms\Components\TextInput::make('condiciones')
                    ->columnSpan(2)->default('CONTADO'),
                    Forms\Components\Select::make('moneda')
                        ->label('Moneda')
                        ->options([
                            'MXN' => 'MXN - Peso Mexicano', 'USD' => 'USD - Dólar'
                        ])
                        ->default('MXN')
                        ->live(),
                    Forms\Components\TextInput::make('tcambio')
                        ->label('Tipo de Cambio')
                        ->numeric()
                        ->default(1)
                        ->rule('gte:0')
                        ->visible(fn(Forms\Get $get) => $get('moneda') !== 'MXN')
                        ->required(fn(Forms\Get $get) => $get('moneda') !== 'MXN')
                        ->prefix('$')
                        ->currencyMask(decimalSeparator:'.', precision:6),
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
                            ->currencyMask(decimalSeparator:'.',precision:2)
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $cant = $get('cant');
                                $cost = $get('precio');
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
                                $set('clie',$get('../../clie'));
                                Self::updateTotals($get,$set);
                            }),
                            TextInput::make('item')
                                ->live(onBlur:true)
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $cli = $get('../../clie');
                                    $prod = Inventario::where('id',$get('item'))->get();
                                    $prod = $prod[0];
                                    $set('descripcion',$prod->descripcion);
                                    $set('unidad',$prod->unidad ?? 'H87');
                                    $set('cvesat',$prod->cvesat ?? '01010101');
                                    $set('costo',$prod->p_costo);
                                    $clie = Clientes::where('id',$cli)->get();
                                    $clie = $clie[0];
                                    $precio = 0;
                                    switch($clie->lista)
                                    {
                                        case 1: $precio = $prod->precio1; break;
                                        case 2: $precio = $prod->precio2; break;
                                        case 3: $precio = $prod->precio3; break;
                                        case 4: $precio = $prod->precio4; break;
                                        case 5: $precio = $prod->precio5; break;
                                        default: $precio = $prod->precio1; break;
                                    }
                                    $desc = $clie->descuento * 0.01;
                                    $prec = $precio * $desc;
                                    $precio = $precio - $prec;
                                    $set('precio',$precio);
                                })->suffixAction(
                                    ActionsAction::make('AbreItem')
                                    ->icon('fas-circle-question')
                                    ->form([
                                        Select::make('SelItem')
                                        ->label('Seleccionar')
                                        ->searchable()
                                        ->options(Inventario::where('team_id',Filament::getTenant()->id)->pluck('descripcion','id'))
                                    ])
                                    ->action(function(Set $set,Get $get,$data){
                                        $cli = $get('../../clie');
                                        $cant = $get('cant');
                                        $item = $data['SelItem'];
                                        $set('item',$item);
                                        $prod = Inventario::where('id',$item)->get();
                                        $prod = $prod[0];
                                        $set('descripcion',$prod->descripcion);
                                        $set('unidad',$prod->unidad ?? 'H87');
                                        $set('cvesat',$prod->cvesat ?? '01010101');
                                        $set('costo',$prod->p_costo);
                                        $clie = Clientes::where('id',$cli)->get();
                                        $clie = $clie[0];
                                        $precio = 0;
                                        switch($clie->lista)
                                        {
                                            case 1: $precio = $prod->precio1; break;
                                            case 2: $precio = $prod->precio2; break;
                                            case 3: $precio = $prod->precio3; break;
                                            case 4: $precio = $prod->precio4; break;
                                            case 5: $precio = $prod->precio5; break;
                                            default: $precio = $prod->precio1; break;
                                        }
                                        $desc = $clie->descuento * 0.01;
                                        $prec = $precio * $desc;
                                        $precio = $precio - $prec;
                                        $set('precio',$precio);
                                        $subt = $precio * $cant;
                                        $set('subtotal',$subt);
                                        $ivap = $get('../../esquema');
                                        $esq = Esquemasimp::where('id',$ivap)->get();
                                        $esq = $esq[0];
                                        $set('iva',$subt * ($esq->iva*0.01));
                                        $set('retiva',$subt * ($esq->retiva*0.01));
                                        $set('retisr',$subt * ($esq->retisr*0.01));
                                        $set('ieps',$subt * ($esq->ieps*0.01));
                                        $ivapar = $subt * ($esq->iva*0.01);
                                        $retivapar = $subt * ($esq->retiva*0.01);
                                        $retisrpar = $subt * ($esq->retisr*0.01);
                                        $iepspar = $subt * ($esq->ieps*0.01);
                                        $tot = $subt + $ivapar - $retivapar - $retisrpar + $iepspar;
                                        $set('total',$tot);
                                        $set('clie',$get('../../clie'));
                                        Self::updateTotals($get,$set);
                                    })
                            ),
                            TextInput::make('descripcion'),
                            TextInput::make('precio')
                                ->numeric()
                                ->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2)
                                ->live()
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $cant = $get('cant');
                                    $cost = $get('precio');
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
                                    $set('clie',$get('../../clie'));
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
                            Hidden::make('clie'),
                            Hidden::make('observa'),
                            Hidden::make('siguiente'),
                            Hidden::make('costo'),
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
                        ActionsAction::make('ImportarExcel')
                            ->visible(function(Get $get){
                                if($get('clie') > 0&&$get('subtotal') == 0) return true;
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
                            ActionsAction::make('Imprimir Cotizacion')
                            ->badge()->tooltip('Imprimir Cotizacion')
                            ->icon('fas-print')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitAction('')
                            ->modalContent(function(Get $get){
                                $idorden = $get('id');
                                if($idorden != null)
                                {
                                    $archivo = public_path('/Reportes/Cotizacion.pdf');
                                    if(File::exists($archivo)) unlink($archivo);
                                    SnappyPdf::loadView('RepCotizacion',['idorden'=>$idorden])
                                        ->setOption('encoding', 'utf-8')
                                        ->setOption("enable-local-file-access",true)
                                        ->save($archivo);
                                }
                            })->form([
                                PdfViewerField::make('archivo')
                                ->fileUrl(env('APP_URL').'/Reportes/Cotizacion.pdf')
                            ])
                    ])->visibleOn('edit'),
                    Actions::make([
                        ActionsAction::make('Enlazar Orden')
                            ->visible(false)
                            ->badge()->tooltip('Enlazar Orden de Compra')
                            ->icon('fas-file-import')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitActionLabel('Seleccionar')
                            ->form([
                                Select::make('OrdenC')
                                ->searchable()
                                ->label('Seleccionar Orden de Compra')
                                ->options(
                                    Cotizaciones::whereIn('estado',['Activa','Parcial'])
                                    ->select(DB::raw("concat('Folio: ',folio,' Fecha: ',fecha,' Proveedor: ',nombre,' Importe: ',total) as Orden"),'id')
                                    ->pluck('Orden','id'))
                            ])->action(function(Get $get,Set $set,$data){
                                $selorden = $data['OrdenC'];
                                $set('orden',$selorden);
                                $orden = Cotizaciones::where('id',$data['OrdenC'])->get();
                                $Opartidas = CotizacionesPartidas::where('ordenes_id',$data['OrdenC'])->get();
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
            ->recordClasses('row_gral')
        ->defaultPaginationPageOption(5)
        ->paginationPageOptions([5,'all'])
        ->striped()
        ->columns([
            Tables\Columns\TextColumn::make('docto')
                ->label('Cotizacion')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('fecha')
                ->date('d-m-Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('nombre')
                ->searchable()
                ->label('Cliente'),
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
            Tables\Columns\TextColumn::make('moneda')
                ->label('Moneda')
                ->searchable(),
            Tables\Columns\TextColumn::make('tcambio')
                ->label('T.Cambio')
                ->numeric()
                ->formatStateUsing(fn($state) => number_format((float)$state, 6))
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('estado')
                ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Cancelar')
                ->icon('fas-ban')
                ->tooltip('Cancelar')->label('')
                ->color(Color::Red)
                ->badge()
                ->requiresConfirmation()
                ->action(function(Model $record){
                    $est = $record->estado;
                    if($est == 'Activa')
                    {
                        Cotizaciones::where('id',$record->id)->update([
                            'estado'=>'Cancelada'
                        ]);
                        Notification::make()
                        ->title('Cotizacion Cancelada')
                        ->success()
                        ->send();
                    }
                }),
                Action::make('Imprimir_Doc')
                ->label('')->icon(null)->visible(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction('')
                ->modalContent(function($record){
                    $idorden = $record->id;
                    if($idorden != null)
                    {
                        $archivo = public_path('/Reportes/Cotizacion.pdf');
                        if(File::exists($archivo)) unlink($archivo);
                        SnappyPdf::loadView('RepCotizacion',['idorden'=>$idorden])
                            ->setOption("enable-local-file-access",true)
                            ->setOption('encoding', 'utf-8')
                            ->save($archivo);
                        $ruta = env('APP_URL').'/Reportes/Cotizacion.pdf';
                        //dd($ruta);
                    }
                })->form([
                    PdfViewerField::make('archivo')
                    ->fileUrl(env('APP_URL').'/Reportes/Cotizacion.pdf')
                ]),
                Tables\Actions\EditAction::make()
                ->label('')->icon(null)
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('7xl')
                ->after(function($record,$livewire){
                    $livewire->callImprimir($record);
                })->iconPosition(IconPosition::After),
            ])
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nueva Cotizacion')
                ->label('Agregar')->icon('fas-circle-plus')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('7xl')->badge()
                ->after(function($record,$livewire){
                    $livewire->callImprimir($record);
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
            'index' => Pages\ListCotizaciones::route('/'),
            //'create' => Pages\CreateCotizaciones::route('/create'),
            //'edit' => Pages\EditCotizaciones::route('/{record}/edit'),
        ];
    }
}
