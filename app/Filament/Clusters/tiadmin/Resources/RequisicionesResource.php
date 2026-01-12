<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\RequisicionesResource\Pages;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Requisiciones;
use App\Models\Proveedores;
use App\Models\Proyectos;
use App\Models\RequisicionesPartidas;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class RequisicionesResource extends Resource
{
    protected static ?string $model = Requisiciones::class;
    protected static ?int $navigationSort = 1;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras']);
    }
    protected static ?string $navigationIcon = 'fas-file-lines';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $label = 'Requisición';
    protected static ?string $pluralLabel = 'Requisiciones de Compra';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(6)
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Split::make([
                    FieldSet::make('Requisición de Compra')
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\TextInput::make('folio')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->default(function(){
                                $las_fol = Requisiciones::where('team_id',Filament::getTenant()->id)->max('folio') ?? 0;
                                return  $las_fol + 1;
                            }),
                        Forms\Components\Select::make('prov')
                            ->searchable()
                            ->label('Proveedor')
                            ->columnSpan(3)
                            ->live(onBlur: true)
                            ->options(Proveedores::all()->pluck('nombre','id'))
                            ->afterStateUpdated(function(Get $get,Set $set){
                                $prov = Proveedores::where('id',$get('prov'))->get();
                                if(count($prov) > 0){
                                    $prov = $prov[0];
                                    $set('nombre',$prov->nombre);
                                } else {
                                    $set('nombre', null);
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
                                        if(count($prod) > 0){
                                            $prod = $prod[0];
                                            $set('descripcion',$prod->descripcion);
                                            $set('costo',$prod->u_costo);
                                        }
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
                                    if($get('subtotal') == 0) return true;
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
                                                if(count($prod) > 0){
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
                                            }
                                            $r++;
                                        }
                                    $set('partidas', $partidas);
                                    Self::updateTotals2($get,$set);
                                })
                            ]),
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
                        Requisiciones::where('id',$record->id)->update([
                            'estado'=>'Cancelada'
                        ]);
                        Notification::make()
                        ->title('Requisición Cancelada')
                        ->success()
                        ->send();
                    }
                }),
                ActionsAction::make('Generar Orden')
                    ->icon('fas-file-invoice-dollar')
                    ->color(Color::Green)
                    ->visible(fn($record) => in_array($record->estado, ['Activa','Parcial']))
                    ->mountUsing(function (Forms\ComponentContainer $form, Model $record) {
                        $partidas = $record->partidas()
                            ->where(function($q) {
                                $q->whereNull('pendientes')->orWhere('pendientes', '>', 0);
                            })
                            ->get()
                            ->map(function ($partida) {
                                return [
                                    'partida_id' => $partida->id,
                                    'item' => $partida->item,
                                    'descripcion' => $partida->descripcion,
                                    'cantidad_original' => $partida->cant,
                                    'cantidad_pendiente' => $partida->pendientes ?? $partida->cant,
                                    'cantidad_a_convertir' => $partida->pendientes ?? $partida->cant,
                                    'costo' => $partida->costo,
                                ];
                            })->toArray();

                        $form->fill([
                            'partidas' => $partidas,
                        ]);
                    })
                    ->form([
                        Forms\Components\Section::make('Información de la Requisición')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('origen_folio')
                                            ->label('Folio Requisición')
                                            ->content(fn ($record) => $record->folio),
                                        Forms\Components\Placeholder::make('origen_fecha')
                                            ->label('Fecha')
                                            ->content(fn ($record) => $record->fecha),
                                        Forms\Components\Placeholder::make('origen_proveedor')
                                            ->label('Proveedor')
                                            ->content(fn ($record) => $record->nombre),
                                    ]),
                            ]),
                        Forms\Components\Repeater::make('partidas')
                            ->label('Partidas Pendientes')
                            ->schema([
                                Forms\Components\Hidden::make('partida_id'),
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Placeholder::make('item_desc')
                                            ->label('Producto / Descripción')
                                            ->content(fn ($get) => ($get('item') ? '[' . \App\Models\Inventario::find($get('item'))?->clave . '] ' : '') . $get('descripcion'))
                                            ->columnSpan(2),
                                        Forms\Components\Placeholder::make('pendiente')
                                            ->label('Pendiente')
                                            ->content(fn ($get) => $get('cantidad_pendiente')),
                                        Forms\Components\TextInput::make('cantidad_a_convertir')
                                            ->label('A Convertir')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0.01)
                                            ->maxValue(fn ($get) => $get('cantidad_pendiente'))
                                            ->reactive(),
                                    ]),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                    ])
                    ->action(function (Model $record, array $data) {
                        $req = $record->fresh();

                        $partidasSeleccionadas = collect($data['partidas'])->filter(fn($p) => $p['cantidad_a_convertir'] > 0);

                        if ($partidasSeleccionadas->isEmpty()) {
                            Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                            return;
                        }

                        DB::beginTransaction();
                        try {
                            // Crear Orden
                            $orden = \App\Models\Ordenes::create([
                                'folio' => (\App\Models\Ordenes::where('team_id', Filament::getTenant()->id)->max('folio') ?? 0) + 1,
                                'fecha' => now()->format('Y-m-d'),
                                'prov' => $req->prov,
                                'nombre' => $req->nombre,
                                'esquema' => $req->esquema,
                                'subtotal' => 0,
                                'iva' => 0,
                                'retiva' => 0,
                                'retisr' => 0,
                                'ieps' => 0,
                                'total' => 0,
                                'moneda' => $req->moneda,
                                'tcambio' => $req->tcambio ?? 1,
                                'observa' => 'Generada desde Requisición #'.$req->folio,
                                'estado' => 'Activa',
                                'requisicion_id' => $req->id,
                                'team_id' => Filament::getTenant()->id,
                            ]);

                            $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;

                            foreach ($partidasSeleccionadas as $pData) {
                                $parOriginal = \App\Models\RequisicionesPartidas::find($pData['partida_id']);
                                if (!$parOriginal) continue;

                                $cantConvertir = $pData['cantidad_a_convertir'];
                                $unit = $parOriginal->costo;

                                // Calcular proporcionales de impuestos basándose en la cantidad original de la partida de la requisición
                                $factor = $parOriginal->cant > 0 ? ($cantConvertir / $parOriginal->cant) : 0;

                                $lineSubtotal = $unit * $cantConvertir;
                                $lineIva = $parOriginal->iva * $factor;
                                $lineRetIva = $parOriginal->retiva * $factor;
                                $lineRetIsr = $parOriginal->retisr * $factor;
                                $lineIeps = $parOriginal->ieps * $factor;
                                $lineTotal = $lineSubtotal + $lineIva + $lineIeps - $lineRetIva - $lineRetIsr;

                                \App\Models\OrdenesPartidas::create([
                                    'ordenes_id' => $orden->id,
                                    'item' => $parOriginal->item,
                                    'descripcion' => $parOriginal->descripcion,
                                    'cant' => $cantConvertir,
                                    'pendientes' => $cantConvertir, // Para la orden, todo está pendiente de recibir en compra
                                    'costo' => $unit,
                                    'subtotal' => $lineSubtotal,
                                    'iva' => $lineIva,
                                    'retiva' => $lineRetIva,
                                    'retisr' => $lineRetIsr,
                                    'ieps' => $lineIeps,
                                    'total' => $lineTotal,
                                    'unidad' => $parOriginal->unidad,
                                    'cvesat' => $parOriginal->cvesat,
                                    'prov' => $parOriginal->prov ?? $req->prov,
                                    'observa' => 'Desde Requisición #'.$req->folio.' partida #'.$parOriginal->id,
                                    'team_id' => Filament::getTenant()->id,
                                    'requisicion_partida_id' => $parOriginal->id,
                                ]);

                                // Actualizar pendientes en Requisición
                                $parOriginal->pendientes = max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantConvertir);
                                $parOriginal->save();

                                $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva; $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;
                            }

                            $orden->update([
                                'subtotal' => $subtotal,
                                'iva' => $iva,
                                'retiva' => $retiva,
                                'retisr' => $retisr,
                                'ieps' => $ieps,
                                'total' => $total,
                            ]);

                            // Actualizar estado de la requisición
                            $quedanPend = \App\Models\RequisicionesPartidas::where('requisiciones_id', $req->id)
                                ->where(function($q){ $q->whereNull('pendientes')->orWhere('pendientes','>',0); })
                                ->exists();

                            $req->estado = $quedanPend ? 'Parcial' : 'Cerrada';
                            $req->save();

                            DB::commit();
                            Notification::make()->title('Orden generada #'.$orden->folio)->success()->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Error al generar la orden: ' . $e->getMessage())->danger()->send();
                        }
                    })
            ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nueva Requisición')->badge()
                ->label('Agregar')->icon('fas-circle-plus')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('full')->button()
                ->after(function ($record){
                    $partidas = RequisicionesPartidas::where('requisiciones_id',$record->id)->get();
                    foreach ($partidas as $p) {
                        $p->pendientes = $p->cant;
                        $p->save();
                    }
                })
            ],HeaderActionsPosition::Bottom)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListRequisiciones::route('/'),
        ];
    }
}
