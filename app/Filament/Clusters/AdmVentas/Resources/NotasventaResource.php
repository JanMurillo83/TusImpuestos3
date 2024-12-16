<?php

namespace App\Filament\Clusters\AdmVentas\Resources;

use App\Filament\Clusters\AdmVentas;
use App\Filament\Clusters\AdmVentas\Resources\NotasventaResource\Pages;
use App\Filament\Clusters\AdmVentas\Resources\NotasventaResource\RelationManagers;
use App\Models\Notasventa;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Movinventario;
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

class NotasventaResource extends Resource
{
    protected static ?string $model = Notasventa::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'fas-receipt';
    protected static ?string $label = 'Nota de Venta';
    protected static ?string $pluralLabel = 'Notas de Venta';
    protected static ?string $cluster = AdmVentas::class;

    public static function form(Form $form): Form
    {
        return $form
        ->columns(6)
        ->schema([
            Split::make([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Fieldset::make('Nota de Venta')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Hidden::make('serie')->default('V'),
                        Forms\Components\Hidden::make('folio')
                        ->default(function(){
                            return count(Notasventa::all()) + 1;
                        }),
                        Forms\Components\TextInput::make('docto')
                        ->label('Documento')
                        ->required()
                        ->readOnly()
                        ->default(function(){
                            $fol = count(Notasventa::all()) + 1;
                            return 'V'.$fol;
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
                        ->options(Esquemasimp::all()->pluck('descripcion','id'))
                        ->default(1)->disabledOn('edit'),
                    Forms\Components\Textarea::make('observa')
                        ->columnSpan(3)->label('Observaciones')
                        ->rows(1),
                    Forms\Components\TextInput::make('condiciones')
                    ->columnSpan(2)->default('CONTADO'),
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
                                        ->options(Inventario::all()->pluck('descripcion','id'))
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
                            ActionsAction::make('Imprimir Nota')
                            ->badge()->tooltip('Imprimir Nota de Venta')
                            ->icon('fas-print')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitAction('')
                            ->modalContent(function(Get $get){
                                $idorden = $get('id');
                                if($idorden != null)
                                {
                                    $archivo = public_path('/Reportes/Notadeventa.pdf');
                                    if(File::exists($archivo)) unlink($archivo);
                                    SnappyPdf::loadView('RepNotaVenta',['idorden'=>$idorden])
                                        ->setOption("footer-right", "Pagina [page] de [topage]")
                                        ->setOption('encoding', 'utf-8')
                                        ->save($archivo);
                                    $ruta = env('APP_URL').'/Reportes/RepNotaVenta.pdf';
                                    //dd($ruta);
                                }
                            })->form([
                                PdfViewerField::make('archivo')
                                ->fileUrl(env('APP_URL').'/Reportes/Notadeventa.pdf')
                            ])
                    ])->visibleOn('edit'),
                    Actions::make([
                        ActionsAction::make('Enlazar Cotizacion')
                            ->badge()->tooltip('Enlazar Cotizacion')
                            ->icon('fas-file-import')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitActionLabel('Seleccionar')
                            ->form([
                                Select::make('OrdenC')
                                ->searchable()
                                ->label('Seleccionar Cotizacion')
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
        ->defaultPaginationPageOption(5)
        ->paginationPageOptions([5,'all'])
        ->striped()
        ->columns([
            Tables\Columns\TextColumn::make('docto')
                ->label('Nota de Venta')
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
                        Notasventa::where('id',$record->id)->update([
                            'estado'=>'Cancelada'
                        ]);
                        Notification::make()
                        ->title('Nota Cancelada')
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
                        $archivo = public_path('/Reportes/Notadeventa.pdf');
                        if(File::exists($archivo)) unlink($archivo);
                        SnappyPdf::loadView('RepNotaVenta',['idorden'=>$idorden])
                            ->setOption("footer-right", "Pagina [page] de [topage]")
                            ->setOption('encoding', 'utf-8')
                            ->save($archivo);
                        $ruta = env('APP_URL').'/Reportes/Notadeventa.pdf';
                        //dd($ruta);
                    }
                })->form([
                    PdfViewerField::make('archivo')
                    ->fileUrl(env('APP_URL').'/Reportes/Notadeventa.pdf')
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
                ->tooltip('Nueva Nota de venta')
                ->label('Agregar')->icon('fas-circle-plus')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('7xl')->badge()
                ->after(function($record,$livewire){
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
                                'tipo'=>'Salida',
                                'fecha'=>Carbon::now(),
                                'cant'=>$partida->cant,
                                'costo'=>$partida->costo,
                                'precio'=>$partida->precio,
                                'concepto'=>6,
                                'tipoter'=>'C',
                                'tercero'=>$record->clie
                            ]);

                            $cost = $partida->costo;
                            $cant = $inve->exist - $partida->cant;
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
                        $nopar++;
                    }
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
            'index' => Pages\ListNotasventas::route('/'),
            //'create' => Pages\CreateNotasventa::route('/create'),
            //'edit' => Pages\EditNotasventa::route('/{record}/edit'),
        ];
    }
}
