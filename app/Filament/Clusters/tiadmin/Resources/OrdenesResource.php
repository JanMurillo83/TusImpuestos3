<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\OrdenesResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\OrdenesResource\RelationManagers;
use App\Models\Compras;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Ordenes;
use App\Models\Proveedores;
use App\Models\Proyectos;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
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
use Illuminate\Support\Facades\View;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use Spatie\Browsershot\Browsershot;

class OrdenesResource extends Resource
{
    protected static ?string $model = Ordenes::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'fas-cart-arrow-down';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $label = 'Orden';
    protected static ?string $pluralLabel = 'Ordenes de Compras';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(6)
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Split::make([
                    FieldSet::make('Orden de Compra')
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\TextInput::make('folio')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->default(function(){
                                $las_fol = Ordenes::where('team_id',Filament::getTenant()->id)->max('folio') ?? 0;
                                return  $las_fol + 1;
                            }),
                        Forms\Components\Select::make('prov')
                            ->searchable()
                            ->label('Proveedor')
                            ->columnSpan(3)
                            ->live(onBlur: true)
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
                            ->options(Esquemasimp::where('team_id',Filament::getTenant()->id)->pluck('descripcion','id'))
                            ->default(Esquemasimp::where('team_id',Filament::getTenant()->id)->first()->id)->disabledOn('edit'),
                        Forms\Components\Select::make('moneda')
                            ->options(['MXN'=>'MXN','USD'=>'USD'])
                            ->default('MXN')
                            ->disabledOn('edit')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function(Get $get,Set $set){
                                $mon = $get('moneda');
                                if($mon == 'MXN') $set('tcambio',1.00);
                            }),
                        Forms\Components\TextInput::make('tcambio')
                            ->label('Tipo de Cambio')
                            ->required()
                            ->reactive()
                            ->disabled(function(Get $get){
                                $mon = $get('moneda');
                                if($mon == 'MXN') return true;
                                else return false;
                            })
                            ->numeric()
                            ->prefix('$')
                            ->default(1.00)->currencyMask(),
                        TextInput::make('solicita')->columnSpan(2),
                        Select::make('proyecto')
                            ->options(Proyectos::where('team_id',Filament::getTenant()->id)->pluck('descripcion','id'))
                            ->columnSpan(2),
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
                                ->live(onBlur: true)
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
                                        ->icon('fas-magnifying-glass')
                                        ->form([
                                            Select::make('SelItem')
                                            ->label('Seleccionar')
                                            ->searchable()
                                            ->options(DB::table('inventarios')->where('team_id',Filament::getTenant()->id)
                                                ->select(DB::raw('CONCAT(clave," - ",descripcion) as descripcion,id'))
                                                ->pluck('descripcion','id'))
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
                                    ->live(onBlur: true)
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
                                Hidden::make('prov'),
                                Hidden::make('team_id')->default(Filament::getTenant()->id),
                            ])->columnSpan('full')->streamlined(),
                            Forms\Components\Textarea::make('observa')
                                ->columnSpanFull()->label('Observaciones')
                                ->rows(3),

                        ])->grow(true)->columns(5)
                    ->columnSpanFull(),
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
                            Action::make('Imprimir Orden')
                                ->badge()->tooltip('Imprimir Orden')
                                ->icon('fas-print')
                                ->modalCancelActionLabel('Cerrar')
                                ->modalSubmitAction('')
                                ->action(function($record){
                                    $idorden = $record->id;
                                    $id_empresa = Filament::getTenant()->id;
                                    $archivo_pdf = 'ORDEN_COMPRA'.$record->id.'.pdf';
                                    $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                    if(File::exists($ruta))File::delete($ruta);
                                    $data = ['idorden'=>$idorden,'id_empresa'=>$id_empresa];
                                    $html = View::make('RepOrden',$data)->render();
                                    Browsershot::html($html)->format('Letter')
                                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                        ->noSandbox()
                                        ->scale(0.8)->savePdf($ruta);
                                    return response()->download($ruta);
                                })
                        ])->visibleOn('edit'),
                        ])->grow(false),

                ])->columnSpanFull(),
                Forms\Components\Hidden::make('nombre'),
                Forms\Components\Hidden::make('estado')->default('Activa'),
                Forms\Components\Hidden::make('compra')->default(0),
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
                Tables\Columns\TextColumn::make('moneda'),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make()
                ->label('Editar')->icon('fas-edit')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('full')
                ->visible(function ($record) {
                    if($record->estado == 'Activa') return true;
                    else return false;
                }),
                Tables\Actions\Action::make('Imprimir')->icon('fas-print')
                    ->action(function($record){
                        $idorden = $record->id;
                        $id_empresa = Filament::getTenant()->id;
                        $archivo_pdf = 'ORDEN_COMPRA'.$record->id.'.pdf';
                        $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                        if(File::exists($ruta))File::delete($ruta);
                        $data = ['idorden'=>$idorden,'id_empresa'=>$id_empresa];
                        $html = View::make('RepOrden',$data)->render();
                        Browsershot::html($html)->format('Letter')
                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                            ->noSandbox()
                            ->scale(0.5)->savePdf($ruta);
                        return response()->download($ruta);
                    }),
                Tables\Actions\ViewAction::make()
                    ->modalWidth('full')
                    ->visible(function ($record) {
                        if($record->estado == 'Activa') return false;
                        else return true;
                    }),
                ActionsAction::make('Cancelar')
                ->icon('fas-ban')
                ->label('Cancelar')
                ->color(Color::Red)
                ->requiresConfirmation()
                ->action(function(Model $record){
                    $est = $record->estado;
                    if($est == 'Activa')
                    {
                        Ordenes::where('id',$record->id)->update([
                            'estado'=>'Cancelada'
                        ]);
                        Notification::make()
                        ->title('Orden Cancelada')
                        ->success()
                        ->send();
                    }
                }),
                ActionsAction::make('Generar Compra')
                    ->icon('fas-file-invoice')
                    ->color(Color::Green)
                    ->visible(fn($record) => in_array($record->estado, ['Activa','Parcial']))
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad a recibir (vacío = pendientes)')
                            ->numeric()
                            ->nullable(),
                    ])
                    ->action(function(Model $record, array $data){
                        $ord = $record->fresh();
                        if (!in_array($ord->estado, ['Activa','Parcial'])) {
                            Notification::make()->title('Estado no válido')->danger()->send();
                            return;
                        }
                        // Crear Compra
                        $compra = \App\Models\Compras::create([
                            'folio' => (\App\Models\Compras::where('team_id', Filament::getTenant()->id)->max('folio') ?? 0) + 1,
                            'fecha' => now()->format('Y-m-d'),
                            'prov' => $ord->prov,
                            'nombre' => $ord->nombre,
                            'esquema' => $ord->esquema,
                            'subtotal' => 0,
                            'iva' => 0,
                            'retiva' => 0,
                            'retisr' => 0,
                            'ieps' => 0,
                            'total' => 0,
                            'moneda' => $ord->moneda,
                            'tcambio' => $ord->tcambio ?? 1,
                            'observa' => 'Generada desde Orden #'.$ord->folio,
                            'estado' => 'Activa',
                            'orden' => $ord->id,
                            'orden_id' => $ord->id,
                            'requisicion_id' => $ord->requisicion_id,
                            'team_id' => Filament::getTenant()->id,
                        ]);
                        $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;
                        $partidas = \App\Models\OrdenesPartidas::where('ordenes_id', $ord->id)->get();
                        foreach ($partidas as $par) {
                            $pend = $par->pendientes ?? $par->cant;
                            if ($pend <= 0) continue;
                            $cantRecibir = $pend;
                            if (!empty($data['cantidad']) && $data['cantidad'] > 0) {
                                $cantRecibir = min($pend, $data['cantidad']);
                            }
                            $unit = $par->costo;
                            $lineSubtotal = $unit * $cantRecibir;
                            $factor = $par->cant > 0 ? ($cantRecibir / $par->cant) : 0;
                            $lineIva = $par->iva * $factor;
                            $lineRetIva = $par->retiva * $factor;
                            $lineRetIsr = $par->retisr * $factor;
                            $lineIeps = $par->ieps * $factor;
                            $lineTotal = $par->total * $factor;

                            \App\Models\ComprasPartidas::create([
                                'compras_id' => $compra->id,
                                'item' => $par->item,
                                'descripcion' => $par->descripcion,
                                'cant' => $cantRecibir,
                                'costo' => $unit,
                                'subtotal' => $lineSubtotal,
                                'iva' => $lineIva,
                                'retiva' => $lineRetIva,
                                'retisr' => $lineRetIsr,
                                'ieps' => $lineIeps,
                                'total' => $lineTotal,
                                'unidad' => $par->unidad,
                                'cvesat' => $par->cvesat,
                                'prov' => $par->prov ?? $ord->prov,
                                'observa' => 'Desde Orden partida #'.$par->id,
                                'idorden' => $ord->id,
                                'orden_partida_id' => $par->id,
                                'requisicion_partida_id' => $par->requisicion_partida_id,
                                'moneda' => $ord->moneda,
                                'tcambio' => $ord->tcambio ?? 1,
                                'team_id' => Filament::getTenant()->id,
                            ]);

                            // Actualizar pendientes en orden
                            $par->pendientes = max(0, ($par->pendientes ?? $par->cant) - $cantRecibir);
                            $par->save();

                            $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva; $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;
                        }

                        $compra->update([
                            'subtotal' => $subtotal,
                            'iva' => $iva,
                            'retiva' => $retiva,
                            'retisr' => $retisr,
                            'ieps' => $ieps,
                            'total' => $total,
                        ]);

                        // Actualizar estado de la orden
                        $quedanPend = \App\Models\OrdenesPartidas::where('ordenes_id', $ord->id)
                            ->where(function($q){ $q->whereNull('pendientes')->orWhere('pendientes','>',0); })
                            ->exists();
                        $nuevoEstado = $quedanPend ? 'Parcial' : 'Cerrada';
                        $ord->estado = $nuevoEstado;
                        $ord->save();

                        Notification::make()->title('Compra generada #'.$compra->folio)->success()->send();
                    })
            ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nueva Orden')->badge()
                ->label('Agregar')->icon('fas-circle-plus')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('full')->button()
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
            'index' => Pages\ListOrdenes::route('/'),
            //'create' => Pages\CreateOrdenes::route('/create'),
            //'edit' => Pages\EditOrdenes::route('/{record}/edit'),
        ];
    }
}
