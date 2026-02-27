<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\RequisicionesResource\Pages;
use App\Models\Claves;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Requisiciones;
use App\Models\Proveedores;
use App\Models\Proyectos;
use App\Models\RequisicionesPartidas;
use App\Models\SeriesFacturas;
use App\Models\Unidades;
use App\Services\ImpuestosCalculator;
use App\Support\DocumentFilename;
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
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
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
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras'])
            && $record->estado === 'Activa';
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
                            Forms\Components\Select::make('sel_serie')
                                ->label('Serie')
                                ->live(onBlur: true)
                                ->required(fn (string $context): bool => $context === 'create')
                                ->disabledOn('edit')
                                ->dehydrated(fn (string $context): bool => $context === 'create')
                                ->options(SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                    ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                    ->select(DB::raw("id,CONCAT(serie,'-',COALESCE(descripcion,'Default')) as descripcion"))
                                    ->pluck('descripcion', 'id'))
                                ->default(function () {
                                    return SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                        ->value('id');
                                })
                                ->afterStateHydrated(function (Set $set, ?Requisiciones $record): void {
                                    if (! $record || ! $record->serie) {
                                        return;
                                    }
                                    $serId = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                        ->where('serie', $record->serie)
                                        ->value('id');
                                    if ($serId) {
                                        $set('sel_serie', $serId);
                                    }
                                })
                                ->afterStateUpdated(function (Get $get, Set $set, $context) {
                                    if ($context === 'edit') {
                                        return;
                                    }
                                    $serId = $get('sel_serie');
                                    if (! $serId) {
                                        return;
                                    }
                                    $fol = SeriesFacturas::find($serId);
                                    if (! $fol) {
                                        return;
                                    }
                                    $set('serie', $fol->serie);
                                    $set('folio', $fol->folio + 1);
                                    $set('docto', $fol->serie . ($fol->folio + 1));
                                }),
                            Forms\Components\Hidden::make('serie')
                                ->default(function () {
                                    return SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                        ->value('serie') ?? 'RQ';
                                }),
                            Forms\Components\Hidden::make('folio')
                                ->default(function () {
                                    $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                        ->first();
                                    return ($serieRow->folio ?? 0) + 1;
                                }),
                            Forms\Components\TextInput::make('docto')
                                ->label('Documento')
                                ->required()
                                ->readOnly()
                                ->default(function () {
                                    $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                        ->first();
                                    $serie = $serieRow->serie ?? 'RQ';
                                    $folio = ($serieRow->folio ?? 0) + 1;
                                    return $serie . $folio;
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
                            ->readOnly(function(Get $get){
                                $mon = $get('moneda');
                                if($mon == 'MXN') return true;
                                else return false;
                            })
                            ->numeric()
                            ->prefix('$')
                            ->default(1.00)->currencyMask(),
                        TextInput::make('solicita')
                            ->default(Filament::auth()->user()->name)
                            ->readOnly()
                            ->columnSpan(2),
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
                                Select::make('item')
                                    ->searchable()
                                    ->options(Inventario::where('team_id', Filament::getTenant()->id)
                                        ->select('id', DB::raw('CONCAT("Item: ",descripcion,"  Exist: ",FORMAT(exist,2)) as descripcion'))
                                        ->pluck('descripcion', 'id'))
                                    ->createOptionForm(function ($form) {
                                        return $form
                                            ->schema([
                                                TextInput::make('clave')->label('SKU')->required(),
                                                TextInput::make('descripcion')->columnSpan(3)->required(),
                                                TextInput::make('precio')->required()->default(0)
                                                    ->currencyMask(decimalSeparator:'.',precision:4),
                                                Forms\Components\TextInput::make('cvesat')
                                                    ->label('Clave SAT')
                                                    ->default(function(Get $get): string{
                                                        if($get('cvesat'))
                                                            $val = $get('cvesat');
                                                        else
                                                            $val = '01010101';
                                                        return $val;
                                                    })
                                                    ->required()
                                                    ->suffixAction(
                                                        \Filament\Forms\Components\Actions\Action::make('Cat_cve_sat')
                                                            ->label('Buscador')
                                                            ->icon('fas-circle-question')
                                                            ->form([
                                                                Forms\Components\TextInput::make('cvesat_search')
                                                                    ->label('Buscar')
                                                                    ->live(debounce: 400),
                                                                Forms\Components\Select::make('CatCveSat')
                                                                    ->label('Claves SAT')
                                                                    ->options(fn (Get $get): array => Claves::getCachedOptions($get('cvesat_search') ?? '', 25))
                                                                    ->reactive(),
                                                            ])
                                                            ->modalCancelAction(false)
                                                            ->modalSubmitActionLabel('Seleccionar')
                                                            ->modalWidth('sm')
                                                            ->action(function(Set $set,$data){
                                                                $set('cvesat',$data['CatCveSat']);
                                                            })
                                                    ),
                                                Select::make('unidad')
                                                    ->label('Unidad de Medida')
                                                    ->searchable()
                                                    ->required()
                                                    ->options(Unidades::all()->pluck('mostrar','clave'))
                                                    ->default('H87'),
                                                Select::make('servicio')->label('Servicio')
                                                    ->options(['SI'=>'SI','NO'=>'NO'])->default('NO'),
                                            ])->columns(4);
                                    })->createOptionUsing(function ($data) {
                                        return Inventario::create([
                                            'clave'=> $data['clave'],
                                            'descripcion'=> $data['descripcion'],
                                            'linea'=>1,
                                            'marca'=>'',
                                            'modelo'=>'',
                                            'u_costo'=>0,
                                            'p_costo'=>0,
                                            'precio1'=> $data['precio'],
                                            'precio2'=>0,
                                            'precio3'=>0,
                                            'precio4'=>0,
                                            'precio5'=>0,
                                            'exist'=>0,
                                            'esquema'=>Esquemasimp::where('team_id',Filament::getTenant()->id)->first()->id,
                                            'servicio'=>$data['servicio'],
                                            'unidad'=>$data['unidad'],
                                            'cvesat'=>$data['cvesat'],
                                            'team_id'=>Filament::getTenant()->id
                                        ])->getKey();
                                    })
                                    ->required()
                                    ->live(onBlur:true)
                                    ->afterStateUpdated(function(Get $get, Set $set){
                                        $prod = Inventario::where('id', $get('item'))->first();
                                        if (!$prod) {
                                            return;
                                        }
                                        $set('descripcion', $prod->descripcion);
                                        $set('costo', $prod->u_costo);
                                        $cant = floatval($get('cant')) ?: 1;
                                        $subt = $prod->u_costo * $cant;
                                        $set('subtotal', $subt);
                                        $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                        $set('iva', $taxes['iva']);
                                        $set('retiva', $taxes['retiva']);
                                        $set('retisr', $taxes['retisr']);
                                        $set('ieps', $taxes['ieps']);
                                        $set('total', $taxes['total']);
                                        $set('prov', $get('../../prov'));
                                        Self::updateTotals($get,$set);
                                    }),
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
                                                    $taxes = ImpuestosCalculator::fromEsquema($get('esquema'), $subt);
                                                    $data = ['cant'=>$cant,'item'=>$prod->id,'descripcion'=>$prod->descripcion,
                                                    'costo'=>$cost,'subtotal'=>$subt,'iva'=>$taxes['iva'],
                                                    'retiva'=>$taxes['retiva'],'retisr'=>$taxes['retisr'],
                                                    'ieps'=>$taxes['ieps'],'total'=>$taxes['total'],'prov'=>$get('prov')];
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Documento')
                    ->formatStateUsing(function ($state, $record) {
                        $serie = $record->serie ?? '';
                        return $serie !== '' ? $serie . $state : $state;
                    })
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
                    ->label('Copiar Requisición')
                    ->requiresConfirmation()
                    ->action(function(Model $record){
                        DB::transaction(function () use ($record) {
                            $teamId = Filament::getTenant()->id;

                            $serie = $record->serie ?? 'RQ';
                            $serieRow = SeriesFacturas::where('team_id', $teamId)
                                ->where('tipo', SeriesFacturas::TIPO_REQUISICIONES)
                                ->where('serie', $serie)
                                ->first();
                            if (! $serieRow) {
                                throw new \Exception('No se encontro una serie de requisiciones configurada.');
                            }
                            $folioData = SeriesFacturas::obtenerSiguienteFolio($serieRow->id);

                            // Crear encabezado de requisición copiada
                            $nueva = new Requisiciones();
                            $nueva->team_id = $teamId;
                            $nueva->serie = $folioData['serie'];
                            $nueva->folio = $folioData['folio'];
                            $nueva->docto = $folioData['docto'];
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
                            $nueva->moneda = $record->moneda;
                            $nueva->tcambio = $record->tcambio ?? 1;
                            $nueva->solicita = $record->solicita;
                            $nueva->proyecto = $record->proyecto;
                            $nueva->save();

                            // Duplicar partidas
                            $partidas = RequisicionesPartidas::where('requisiciones_id', $record->id)->get();
                            foreach ($partidas as $par) {
                                RequisicionesPartidas::create([
                                    'requisiciones_id' => $nueva->id,
                                    'item' => $par->item,
                                    'descripcion' => $par->descripcion,
                                    'cant' => $par->cant,
                                    'precio' => $par->precio,
                                    'subtotal' => $par->subtotal,
                                    'iva' => $par->iva,
                                    'retiva' => $par->retiva,
                                    'retisr' => $par->retisr,
                                    'ieps' => $par->ieps,
                                    'total' => $par->total,
                                    'unidad' => $par->unidad,
                                    'cvesat' => $par->cvesat,
                                    'costo' => $par->costo,
                                    'observa' => $par->observa,
                                    'pendientes' => $par->cant,
                                    'por_imp1' => $par->por_imp1,
                                    'por_imp2' => $par->por_imp2,
                                    'por_imp3' => $par->por_imp3,
                                    'por_imp4' => $par->por_imp4,
                                    'team_id' => $teamId,
                                ]);
                            }

                            Notification::make()
                                ->title('Requisición copiada correctamente: ' . ($nueva->docto ?? $nueva->folio))
                                ->success()
                                ->send();
                        });
                    }),
                Tables\Actions\Action::make('Editar')
                    ->label('Editar')
                    ->icon('fas-edit')
                    ->url(fn (Model $record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(function ($record) {
                        if ($record->estado == 'Activa') {
                            return true;
                        }
                        return false;
                    }),
                Tables\Actions\ViewAction::make()
                    ->modalWidth('full')
                    ->visible(function ($record) {
                        if($record->estado == 'Activa') return false;
                        else return true;
                    }),
                Tables\Actions\Action::make('Imprimir')->icon('fas-print')
                    ->action(function($record){
                        $idrequisicion = $record->id;
                        $id_empresa = Filament::getTenant()->id;
                        $archivo_pdf = DocumentFilename::build('REQUISICION', $record->docto ?? ($record->serie . $record->folio), $record->nombre, $record->fecha);
                        $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                        if(File::exists($ruta))File::delete($ruta);
                        $data = ['idrequisicion'=>$idrequisicion,'team_id'=>$id_empresa,'prov_id'=>$record->prov];
                        $html = View::make('NFTO_Requisicion',$data)->render();
                        Browsershot::html($html)->format('Letter')
                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                            ->noSandbox()
                            ->scale(0.8)->savePdf($ruta);
                        return response()->download($ruta);
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
                    ->visible(fn($record) => in_array($record->estado, ['Activa', 'Parcial']))
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
                            $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                ->where('tipo', SeriesFacturas::TIPO_ORDENES_COMPRA)
                                ->first();
                            if (! $serieRow) {
                                throw new \Exception('No se encontro una serie de ordenes de compra configurada.');
                            }
                            $folioData = SeriesFacturas::obtenerSiguienteFolio($serieRow->id);

                            // Crear Orden
                            $orden = \App\Models\Ordenes::create([
                                'serie' => $folioData['serie'],
                                'folio' => $folioData['folio'],
                                'docto' => $folioData['docto'],
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
                            $ordenLabel = $orden->docto ?? $orden->folio;
                            Notification::make()->title('Orden generada #'.$ordenLabel)->success()->send();
                            $archivo_pdf = DocumentFilename::build('ORDEN_COMPRA', $orden->docto ?? ($orden->serie . $orden->folio), $orden->nombre, $orden->fecha);
                            $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                            if(File::exists($ruta))File::delete($ruta);
                            $data = ['idorden'=>$orden->id,'team_id'=>Filament::getTenant()->id,'prov_id'=>$orden->prov];
                            $html = View::make('NFTO_OrdendeCompra',$data)->render();
                            Browsershot::html($html)->format('Letter')
                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                ->noSandbox()
                                ->scale(0.8)->savePdf($ruta);
                            return response()->download($ruta);
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Error al generar la orden: ' . $e->getMessage())->danger()->send();
                        }
                    })
            ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                Tables\Actions\Action::make('Agregar')
                    ->label('Agregar')
                    ->icon('fas-circle-plus')
                    ->tooltip('Nueva Requisición')
                    ->url(static::getUrl('create'))
                    ->button()
                    ->visible(static::canCreate())
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
            'create' => Pages\CreateRequisiciones::route('/create'),
            'edit' => Pages\EditRequisiciones::route('/{record}/edit'),
        ];
    }
}
