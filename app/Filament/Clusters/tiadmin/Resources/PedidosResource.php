<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\PedidosResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\PedidosResource\RelationManagers;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Pedidos;
use App\Models\PedidosPartidas;
use App\Models\SeriesFacturas;
use App\Services\FacturaFolioService;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
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
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class PedidosResource extends Resource
{
    protected static ?string $model = Pedidos::class;
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationIcon = 'fas-file-lines';
    protected static ?string $label = 'Pedido';
    protected static ?string $pluralLabel = 'Pedidos';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'ventas']);
    }
    protected static ?string $navigationGroup = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(6)
            ->schema([
                Split::make([
                    Fieldset::make('Pedido')
                        ->schema([
                            Hidden::make('team_id')->default(Filament::getTenant()->id),
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\Hidden::make('serie')->default('C'),
                            Forms\Components\Hidden::make('folio')
                                ->default(function(){
                                    return count(Pedidos::where('team_id',Filament::getTenant()->id)->get()) + 1;
                                }),
                            Forms\Components\TextInput::make('docto')
                                ->label('Documento')
                                ->required()
                                ->readOnly()
                                ->default(function(){
                                    $fol = count(Pedidos::where('team_id',Filament::getTenant()->id)->get()) + 1;
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
                                ActionsAction::make('Imprimir Pedido')
                                    ->badge()->tooltip('Imprimir Pedido')
                                    ->icon('fas-print')
                                    ->modalCancelActionLabel('Cerrar')
                                    ->modalSubmitAction('')
                                    ->modalContent(function(Get $get){
                                        $idorden = $get('id');
                                        if($idorden != null)
                                        {
                                            $archivo = public_path('/Reportes/Cotizacion.pdf');
                                            if(File::exists($archivo)) unlink($archivo);
                                            $html = View::make('RepCotizacion', ['idorden'=>$idorden])->render();
                                            Browsershot::html($html)
                                                ->format('Letter')
                                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                                ->setEnvironmentOptions([
                                                    "XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing",
                                                    "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"
                                                ])
                                                ->noSandbox()
                                                ->scale(0.8)
                                                ->savePdf($archivo);
                                        }
                                    })->form([
                                        PdfViewerField::make('archivo')
                                            ->fileUrl(env('APP_URL').'/Reportes/Cotizacion.pdf')
                                    ])
                            ])->visibleOn('edit'),
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
                    ->label('Pedido')
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
                            Pedidos::where('id',$record->id)->update([
                                'estado'=>'Cancelada'
                            ]);
                            Notification::make()
                                ->title('Pedido Cancelado')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('Generar Remisión')
                    ->icon('fas-shipping-fast')
                    ->color(Color::Blue)
                    ->visible(false)
                    ->action(function (Model $record) {
                        $req = $record->fresh();
                        $folio = (\App\Models\Remisiones::where('team_id', Filament::getTenant()->id)->max('folio') ?? 0) + 1;
                        $remision = \App\Models\Remisiones::create([
                            'serie' => 'R',
                            'folio' => $folio,
                            'docto' => 'R' . $folio,
                            'fecha' => now()->format('Y-m-d'),
                            'clie' => $req->clie,
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
                            'observa' => 'Generado desde Pedido #' . $req->folio,
                            'estado' => 'Activa',
                            'pedido_id' => $req->id,
                            'team_id' => Filament::getTenant()->id,
                        ]);

                        $subtotal = 0;
                        $iva = 0;
                        $retiva = 0;
                        $retisr = 0;
                        $ieps = 0;
                        $total = 0;
                        $partidas = \App\Models\PedidosPartidas::where('pedidos_id', $req->id)->get();
                        DB::transaction(function () use ($partidas, $remision, $req, &$subtotal, &$iva, &$retiva, &$retisr, &$ieps, &$total) {
                            foreach ($partidas as $par) {
                                $pend = $par->pendientes ?? $par->cant;
                                if ($pend <= 0) continue;

                                $unit = $par->precio;
                                $lineSubtotal = $unit * $pend;
                                $factor = $par->cant > 0 ? ($pend / $par->cant) : 0;
                                $lineIva = $par->iva * $factor;
                                $lineRetIva = $par->retiva * $factor;
                                $lineRetIsr = $par->retisr * $factor;
                                $lineIeps = $par->ieps * $factor;
                                $lineTotal = $par->total * $factor;

                                \App\Models\RemisionesPartidas::create([
                                    'remisiones_id' => $remision->id,
                                    'item' => $par->item,
                                    'descripcion' => $par->descripcion,
                                    'cant' => $pend,
                                    'pendientes' => $pend,
                                    'precio' => $unit,
                                    'subtotal' => $lineSubtotal,
                                    'iva' => $lineIva,
                                    'retiva' => $lineRetIva,
                                    'retisr' => $lineRetIsr,
                                    'ieps' => $lineIeps,
                                    'total' => $lineTotal,
                                    'unidad' => $par->unidad,
                                    'cvesat' => $par->cvesat,
                                    'clie' => $par->clie ?? $req->clie,
                                    'team_id' => Filament::getTenant()->id,
                                ]);

                                // Descontar inventario
                                $prod = Inventario::find($par->item);
                                if ($prod) {
                                    $prod->exist -= $pend;
                                    $prod->save();
                                }

                                $par->pendientes = 0;
                                $par->save();

                                $subtotal += $lineSubtotal;
                                $iva += $lineIva;
                                $retiva += $lineRetIva;
                                $retisr += $lineRetIsr;
                                $ieps += $lineIeps;
                                $total += $lineTotal;
                            }
                        });

                        $remision->update([
                            'subtotal' => $subtotal,
                            'iva' => $iva,
                            'retiva' => $retiva,
                            'retisr' => $retisr,
                            'ieps' => $ieps,
                            'total' => $total,
                        ]);

                        $req->update(['estado' => 'Cerrada']);
                        Notification::make()->title('Remisión generada #' . $remision->folio)->success()->send();
                    }),
                Action::make('Generar Factura')
                    ->label('Facturar Pedido')
                    ->icon('fas-file-invoice')
                    ->color(Color::Green)
                    ->visible(false)
                    ->mountUsing(function (Forms\ComponentContainer $form, Model $record) {
                        $partidas = PedidosPartidas::where('pedidos_id',$record->id)
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
                                    'cantidad_a_facturar' => $partida->pendientes ?? $partida->cant,
                                    'precio' => $partida->precio,
                                ];
                            })->toArray();
                        $form->fill([
                            'partidas' => $partidas,
                        ]);
                    })
                    ->form([
                        Forms\Components\Section::make('Información del Pedido')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('origen_folio')
                                            ->label('Folio Pedido')
                                            ->content(fn ($record) => $record->folio),
                                        Forms\Components\Placeholder::make('origen_fecha')
                                            ->label('Fecha')
                                            ->content(fn ($record) => $record->fecha),
                                        Forms\Components\Placeholder::make('origen_cliente')
                                            ->label('Cliente')
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
                                        Forms\Components\TextInput::make('cantidad_a_facturar')
                                            ->label('A Facturar')
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
                        $ped = $record->fresh();
                        $partidasSeleccionadas = collect($data['partidas'])->filter(fn($p) => $p['cantidad_a_facturar'] > 0);

                        if ($partidasSeleccionadas->isEmpty()) {
                            Notification::make()->title('Debe seleccionar al menos una partida con cantidad mayor a cero.')->danger()->send();
                            return;
                        }

                        DB::beginTransaction();
                        try {
                            $teamId = Filament::getTenant()->id;
                            $serieRow = SeriesFacturas::where('team_id', $teamId)
                                ->where('tipo', SeriesFacturas::TIPO_FACTURAS)
                                ->first();
                            if (! $serieRow) {
                                throw new \Exception('No se encontró una serie configurada para Facturas.');
                            }

                            $factura = FacturaFolioService::crearConFolioSeguro($serieRow->id, [
                                'fecha' => now()->format('Y-m-d'),
                                'clie' => $ped->clie,
                                'nombre' => $ped->nombre,
                                'esquema' => $ped->esquema,
                                'subtotal' => 0,
                                'iva' => 0,
                                'retiva' => 0,
                                'retisr' => 0,
                                'ieps' => 0,
                                'total' => 0,
                                'moneda' => $ped->moneda ?? 'MXN',
                                'tcambio' => $ped->tcambio ?? 1,
                                'observa' => 'Generada desde Pedido #'.$ped->folio,
                                'estado' => 'Activa',
                                'pedido_id' => $ped->id,
                                'team_id' => $teamId,
                                'metodo' => $ped->metodo ?? 'PUE',
                                'forma' => $ped->forma ?? '01',
                                'uso' => $ped->uso ?? 'G03',
                                'condiciones' => $ped->condiciones ?? 'CONTADO',
                            ]);

                            $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;

                            foreach ($partidasSeleccionadas as $pData) {
                                $parOriginal = \App\Models\PedidosPartidas::find($pData['partida_id']);
                                if (!$parOriginal) continue;

                                $cantFacturar = $pData['cantidad_a_facturar'];
                                $factor = $parOriginal->cant > 0 ? ($cantFacturar / $parOriginal->cant) : 0;

                                $lineSubtotal = $parOriginal->precio * $cantFacturar;
                                $lineIva = $parOriginal->iva * $factor;
                                $lineRetIva = $parOriginal->retiva * $factor;
                                $lineRetIsr = $parOriginal->retisr * $factor;
                                $lineIeps = $parOriginal->ieps * $factor;
                                $lineTotal = $lineSubtotal + $lineIva - $lineRetIva - $lineRetIsr + $lineIeps;

                                \App\Models\FacturasPartidas::create([
                                    'facturas_id' => $factura->id,
                                    'item' => $parOriginal->item,
                                    'descripcion' => $parOriginal->descripcion,
                                    'cant' => $cantFacturar,
                                    'precio' => $parOriginal->precio,
                                    'subtotal' => $lineSubtotal,
                                    'iva' => $lineIva,
                                    'retiva' => $lineRetIva,
                                    'retisr' => $lineRetIsr,
                                    'ieps' => $lineIeps,
                                    'total' => $lineTotal,
                                    'unidad' => $parOriginal->unidad,
                                    'cvesat' => $parOriginal->cvesat,
                                    'costo' => $parOriginal->costo,
                                    'clie' => $ped->clie,
                                    'team_id' => Filament::getTenant()->id,
                                ]);

                                $subtotal += $lineSubtotal; $iva += $lineIva; $retiva += $lineRetIva;
                                $retisr += $lineRetIsr; $ieps += $lineIeps; $total += $lineTotal;

                                $nuevosPendientes = ($parOriginal->pendientes ?? $parOriginal->cant) - $cantFacturar;
                                $parOriginal->update(['pendientes' => max(0, $nuevosPendientes)]);
                            }

                            $factura->update([
                                'subtotal' => $subtotal, 'iva' => $iva, 'retiva' => $retiva,
                                'retisr' => $retisr, 'ieps' => $ieps, 'total' => $total,
                                'docto' => $factura->serie . $factura->folio
                            ]);

                            $pendientesTotales = \App\Models\PedidosPartidas::where('pedidos_id', $ped->id)->sum('pendientes');
                            $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrado' : 'Parcial';
                            $ped->update(['estado' => $nuevoEstado]);

                            DB::commit();
                            Notification::make()->title('Factura generada exitosamente')->success()->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Error al generar factura: ' . $e->getMessage())->danger()->send();
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
                            $html = View::make('RepCotizacion', ['idorden'=>$idorden])->render();
                            Browsershot::html($html)
                                ->format('Letter')
                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                ->setEnvironmentOptions([
                                    "XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing",
                                    "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"
                                ])
                                ->noSandbox()
                                ->scale(0.8)
                                ->savePdf($archivo);
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
                    ->tooltip('Nuevo Pedido')
                    ->label('Agregar')->icon('fas-circle-plus')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->modalWidth('full')
                    ->after(function($record,$livewire){
                        $livewire->callImprimir($record);
                    }),
                Action::make('Importar Cot')
                    ->label('Importar Cotización')
                    ->icon('fas-file-import')
                    ->form(function (Forms\ComponentContainer $form) {
                        return $form->schema([
                            Select::make('sel_cotizacion')->label('Cotización')
                                ->options(
                                    Cotizaciones::whereIn('estado',['Activa','Parcial'])
                                        ->where('team_id', Filament::getTenant()->id)
                                        ->get()->pluck('folio','id')
                                )
                        ]);
                    })
                    ->action(function($data,$livewire){
                        $livewire->requ = $data['sel_cotizacion'];
                        $livewire->getAction('Facturar Cotización')->visible(true);
                        $livewire->replaceMountedAction('Facturar Cotización');
                    }),
            ],HeaderActionsPosition::Bottom);
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
            'index' => Pages\ListPedidos::route('/'),
            //'create' => Pages\CreatePedidos::route('/create'),
            //'edit' => Pages\EditPedidos::route('/{record}/edit'),
        ];
    }
}
