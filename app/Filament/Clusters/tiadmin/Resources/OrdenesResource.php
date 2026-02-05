<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\OrdenesResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\OrdenesResource\RelationManagers;
use App\Models\Compras;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Ordenes;
use App\Models\OrdenesPartidas;
use App\Models\Proveedores;
use App\Models\Proyectos;
use App\Models\Requisiciones;
use App\Models\RequisicionesPartidas;
use App\Services\ImpuestosCalculator;
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
    protected static ?int $navigationSort = 2;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras']);
    }
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
                                    $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                    $set('iva',$taxes['iva']);
                                    $set('retiva',$taxes['retiva']);
                                    $set('retisr',$taxes['retisr']);
                                    $set('ieps',$taxes['ieps']);
                                    $set('total',$taxes['total']);
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
                                        $cant = floatval($get('cant')) ?: 1;
                                        $subt = $prod->u_costo * $cant;
                                        $set('subtotal',$subt);
                                        $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                        $set('iva',$taxes['iva']);
                                        $set('retiva',$taxes['retiva']);
                                        $set('retisr',$taxes['retisr']);
                                        $set('ieps',$taxes['ieps']);
                                        $set('total',$taxes['total']);
                                        Self::updateTotals($get,$set);
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
                                            $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                            $set('iva',$taxes['iva']);
                                            $set('retiva',$taxes['retiva']);
                                            $set('retisr',$taxes['retisr']);
                                            $set('ieps',$taxes['ieps']);
                                            $set('total',$taxes['total']);
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
                                        $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                        $set('iva',$taxes['iva']);
                                        $set('retiva',$taxes['retiva']);
                                        $set('retisr',$taxes['retisr']);
                                        $set('ieps',$taxes['ieps']);
                                        $set('total',$taxes['total']);
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
                            Section::make('Datos de Entrega')
                                ->schema([
                                    Forms\Components\TextInput::make('entrega_lugar')
                                        ->label('Lugar de Entrega'),
                                    Forms\Components\TextInput::make('entrega_direccion')
                                        ->label('Dirección de Entrega'),
                                    Forms\Components\TextInput::make('entrega_horario')
                                        ->label('Horario de Entrega'),
                                    Forms\Components\TextInput::make('entrega_contacto')
                                        ->label('Contacto de Entrega'),
                                    Forms\Components\TextInput::make('entrega_telefono')
                                        ->label('Teléfono de Entrega'),
                                ])->columns(3),
                            Section::make('Datos Comerciales')
                                ->schema([
                                    Forms\Components\TextInput::make('condiciones_pago')
                                        ->label('Condiciones de Pago'),
                                    Forms\Components\TextInput::make('condiciones_entrega')
                                        ->label('Condiciones de Entrega'),
                                    Forms\Components\TextInput::make('oc_referencia_interna')
                                        ->label('Referencia Interna'),
                                    Forms\Components\TextInput::make('nombre_elaboro')
                                        ->label('Elaboró'),
                                    Forms\Components\TextInput::make('nombre_autorizo')
                                        ->label('Autorizó'),
                                ])->columns(3),
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
                                                $taxes = ImpuestosCalculator::fromEsquema($get('esquema'), $subt);
                                                $data = ['cant'=>$cant,'item'=>$prod->id,'descripcion'=>$prod->descripcion,
                                                'costo'=>$cost,'subtotal'=>$subt,'iva'=>$taxes['iva'],
                                                'retiva'=>$taxes['retiva'],'retisr'=>$taxes['retisr'],
                                                'ieps'=>$taxes['ieps'],'total'=>$taxes['total'],'prov'=>$get('prov')];
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
                                    $data = ['idorden'=>$idorden,'team_id'=>$id_empresa,'prov_id'=>$record->prov];
                                    $html = View::make('NFTO_OrdendeCompra',$data)->render();
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
                ActionsAction::make('Copiar')
                    ->icon('fas-copy')
                    ->label('Copiar Orden de Compra')
                    ->requiresConfirmation()
                    ->action(function(Model $record){
                        DB::transaction(function () use ($record) {
                            $teamId = Filament::getTenant()->id;

                            // Obtener nuevo folio
                            $ultimaOrden = Ordenes::where('team_id', $teamId)
                                ->orderBy('folio', 'desc')
                                ->first();
                            $nuevoFolio = ($ultimaOrden->folio ?? 0) + 1;

                            // Crear encabezado de orden copiada
                            $nueva = new Ordenes();
                            $nueva->team_id = $teamId;
                            $nueva->folio = $nuevoFolio;
                            $nueva->fecha = Carbon::now();
                            $nueva->prov = $record->prov;
                            $nueva->nombre = $record->nombre;
                            $nueva->esquema = $record->esquema;
                            $nueva->subtotal = $record->subtotal;
                            $nueva->iva = $record->iva;
                            $nueva->retiva = $record->retiva;
                            $nueva->retisr = $record->retisr;
                            $nueva->ieps = $record->ieps;
                            $nueva->total = $record->total;
                            $nueva->observa = $record->observa;
                            $nueva->estado = 'Activa';
                            $nueva->requisicion_id = $record->requisicion_id;
                            $nueva->entrega_lugar = $record->entrega_lugar;
                            $nueva->entrega_direccion = $record->entrega_direccion;
                            $nueva->entrega_horario = $record->entrega_horario;
                            $nueva->entrega_contacto = $record->entrega_contacto;
                            $nueva->entrega_telefono = $record->entrega_telefono;
                            $nueva->condiciones_pago = $record->condiciones_pago;
                            $nueva->condiciones_entrega = $record->condiciones_entrega;
                            $nueva->oc_referencia_interna = $record->oc_referencia_interna;
                            $nueva->nombre_elaboro = $record->nombre_elaboro;
                            $nueva->nombre_autorizo = $record->nombre_autorizo;
                            $nueva->save();

                            // Duplicar partidas
                            $partidas = OrdenesPartidas::where('ordenes_id', $record->id)->get();
                            foreach ($partidas as $par) {
                                OrdenesPartidas::create([
                                    'ordenes_id' => $nueva->id,
                                    'item' => $par->item,
                                    'descripcion' => $par->descripcion,
                                    'cant' => $par->cant,
                                    'pendientes' => $par->cant,
                                    'costo' => $par->costo,
                                    'subtotal' => $par->subtotal,
                                    'iva' => $par->iva,
                                    'retiva' => $par->retiva,
                                    'retisr' => $par->retisr,
                                    'ieps' => $par->ieps,
                                    'total' => $par->total,
                                    'unidad' => $par->unidad,
                                    'cvesat' => $par->cvesat,
                                    'prov' => $par->prov,
                                    'observa' => $par->observa,
                                    'requisicion_partida_id' => $par->requisicion_partida_id,
                                    'team_id' => $teamId,
                                ]);
                            }

                            Notification::make()
                                ->title('Orden de Compra copiada correctamente: ' . $nueva->folio)
                                ->success()
                                ->send();
                        });
                    }),
                Tables\Actions\EditAction::make()
                ->label('Editar')->icon('fas-edit')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('full')
                ->after(function ($record) {
                    $record->refresh();
                    $record->recalculatePartidasFromItemSchema();
                    $record->recalculateTotalsFromPartidas();
                })
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
                        $data = ['idorden'=>$idorden,'team_id'=>$id_empresa,'prov_id'=>$record->prov];
                        $html = View::make('NFTO_OrdendeCompra',$data)->render();
                        Browsershot::html($html)->format('Letter')
                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                            ->noSandbox()
                            ->scale(0.8)->savePdf($ruta);
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
                        $partidas = OrdenesPartidas::where('ordenes_id',$record->id)->get();
                        foreach($partidas as $p){
                            $cant = $p->cant;
                            RequisicionesPartidas::where('id',$p->requisicion_partida_id)->increment('pendientes',$cant);
                        }
                        Requisiciones::where('id',$record->requisicion_id)->update([
                            'estado'=>'Activa'
                        ]);
                        Notification::make()
                        ->title('Orden Cancelada')
                        ->success()
                        ->send();
                    }
                }),
                ActionsAction::make('Generar Compra')
                    ->label('Recibir Orden')
                    ->icon('fas-file-invoice')
                    ->color(Color::Green)
                    ->visible(false)
                    ->mountUsing(function (Forms\ComponentContainer $form, Model $record) {
                        $partidas = OrdenesPartidas::where('ordenes_id',$record->id)
                            ->get()
                            ->map(function ($partida) {
                                return [
                                    'partida_id' => $partida->id,
                                    'item' => $partida->item,
                                    'descripcion' => $partida->descripcion,
                                    'cantidad_original' => $partida->cant,
                                    'cantidad_pendiente' => $partida->cant,
                                    'cantidad_a_recibir' => $partida->cant,
                                    'costo' => $partida->costo,
                                ];
                            })->toArray();
                        //dd($partidas);
                        $form->fill([
                            'partidas' => $partidas,
                        ]);
                    })
                    ->form([
                        Forms\Components\Section::make('Información de la Orden')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('origen_folio')
                                            ->label('Folio Orden')
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
                                        Forms\Components\TextInput::make('cantidad_a_recibir')
                                            ->label('A Recibir')
                                            ->numeric()
                                            ->required()->readOnly()
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
                        $ord = $record->fresh();

                        $partidasSeleccionadas = collect($data['partidas'])->filter(fn($p) => $p['cantidad_a_recibir'] > 0);

                        if ($partidasSeleccionadas->isEmpty()) {
                            Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                            return;
                        }

                        DB::beginTransaction();
                        try {
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

                            foreach ($partidasSeleccionadas as $pData) {
                                $parOriginal = \App\Models\OrdenesPartidas::find($pData['partida_id']);
                                if (!$parOriginal) continue;

                                $cantRecibir = $pData['cantidad_a_recibir'];
                                $unit = $parOriginal->costo;

                                // Calcular proporcionales de impuestos basándose en la cantidad original de la partida de la orden
                                $factor = $parOriginal->cant > 0 ? ($cantRecibir / $parOriginal->cant) : 0;

                                $lineSubtotal = $unit * $cantRecibir;
                                $lineIva = $parOriginal->iva * $factor;
                                $lineRetIva = $parOriginal->retiva * $factor;
                                $lineRetIsr = $parOriginal->retisr * $factor;
                                $lineIeps = $parOriginal->ieps * $factor;
                                $lineTotal = $lineSubtotal + $lineIva + $lineIeps - $lineRetIva - $lineRetIsr;

                                \App\Models\ComprasPartidas::create([
                                    'compras_id' => $compra->id,
                                    'item' => $parOriginal->item,
                                    'descripcion' => $parOriginal->descripcion,
                                    'cant' => $cantRecibir,
                                    'costo' => $unit,
                                    'subtotal' => $lineSubtotal,
                                    'iva' => $lineIva,
                                    'retiva' => $lineRetIva,
                                    'retisr' => $lineRetIsr,
                                    'ieps' => $lineIeps,
                                    'total' => $lineTotal,
                                    'unidad' => $parOriginal->unidad,
                                    'cvesat' => $parOriginal->cvesat,
                                    'prov' => $parOriginal->prov ?? $ord->prov,
                                    'observa' => 'Desde Orden #'.$ord->folio.' partida #'.$parOriginal->id,
                                    'idorden' => $ord->id,
                                    'orden_partida_id' => $parOriginal->id,
                                    'requisicion_partida_id' => $parOriginal->requisicion_partida_id,
                                    'moneda' => $ord->moneda,
                                    'tcambio' => $ord->tcambio ?? 1,
                                    'team_id' => Filament::getTenant()->id,
                                ]);

                                // Actualizar pendientes en Orden
                                $parOriginal->pendientes = max(0, ($parOriginal->pendientes ?? $parOriginal->cant) - $cantRecibir);
                                $parOriginal->save();

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

                            $ord->estado = $quedanPend ? 'Parcial' : 'Cerrada';
                            $ord->save();

                            DB::commit();
                            Notification::make()->title('Recepción generada #'.$compra->folio)->success()->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Error al generar la Recepción: ' . $e->getMessage())->danger()->send();
                        }
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
                ->after(function ($record) {
                    $record->refresh();
                    $record->recalculatePartidasFromItemSchema();
                    $record->recalculateTotalsFromPartidas();
                    $partidas = OrdenesPartidas::where('ordenes_id',$record->id)->get();
                        foreach ($partidas as $p) {
                            $p->pendientes = $p->cant;
                            $p->save();
                        }
                }),
                Tables\Actions\Action::make('Importar Req')
                ->label('Importar Requisición')
                ->icon('fas-file-import')
                ->form(function (Forms\ComponentContainer $form) {
                    return $form->schema([
                        Select::make('sel_requisicion')->label('Requisición')
                        ->options(
                            Requisiciones::whereIn('estado',['Activa','Parcial'])
                            ->where('team_id', Filament::getTenant()->id)
                            ->get()->pluck('folio','id')
                        )
                    ]);
                })
                ->action(function($data,$livewire){
                    $livewire->requ = $data['sel_requisicion'];
                    $livewire->getAction('Importar Requisición')->visible(true);
                    $livewire->replaceMountedAction('Importar Requisición');
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
            'index' => Pages\ListOrdenes::route('/'),
            //'create' => Pages\CreateOrdenes::route('/create'),
            //'edit' => Pages\EditOrdenes::route('/{record}/edit'),
        ];
    }
}
