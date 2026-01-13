<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\FacturasResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\FacturasResource\RelationManagers;
use App\Http\Controllers\TimbradoController;
use App\Http\Middleware\ApplyTenantScopes;
use App\Models\Almacencfdis;
use App\Models\Claves;
use App\Models\CuentasCobrar;
use App\Models\DatosFiscales;
use App\Models\DoctosRelacionados;
use App\Models\Facturas;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\FacturasPartidas;
use App\Models\Formas;
use App\Models\Inventario;
use App\Models\Mailconfig;
use App\Models\Metodos;
use App\Models\Movinventario;
use App\Models\Notasventa;
use App\Models\NotasventaPartidas;
use App\Models\Pedidos;
use App\Models\PedidosPartidas;
use App\Models\SeriesFacturas;
use App\Models\SurtidoInve;
use App\Models\TableSettings;
use App\Models\Team;
use App\Models\Unidades;
use App\Models\Usos;
use App\Models\Xmlfiles;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use CfdiUtils\Cleaner\Cleaner;
use CfdiUtils\Nodes\XmlNodeUtils;
use DateTime;
use DateTimeZone;
use DOMDocument;
use Dvarilek\FilamentTableSelect\Components\Form\TableSelect;
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
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use http\Client\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use PhpCfdi\CfdiExpresiones\DiscoverExtractor;
use PhpCfdi\CfdiToPdf\CfdiDataBuilder;
use PHPMailer\PHPMailer\PHPMailer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;
use function Laravel\Prompts\text;


class FacturasResource extends Resource
{
    protected static ?string $model = Facturas::class;
    protected static ?int $navigationSort = 2;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'ventas', 'facturista']);
    }
    protected static ?string $navigationIcon = 'fas-file-invoice-dollar';
    protected static ?string $label = 'Factura';
    protected static ?string $pluralLabel = 'Facturas';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Ventas';


    public static function form(Form $form): Form
    {
        return $form
        ->columns(6)
        ->schema([
            Split::make([
                Fieldset::make('Factura')
                    ->schema([
                        Hidden::make('team_id')->default(Filament::getTenant()->id),
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Select::make('sel_serie')
                            ->label('Serie')
                            ->live(onBlur: true)
                            ->options(SeriesFacturas::where('team_id',Filament::getTenant()->id)
                                ->where('tipo','F')
                                ->select(DB::raw("id,CONCAT(serie,'-',COALESCE(descripcion,'Default')) as descripcion"))
                                ->pluck('descripcion','id'))
                                ->default(function (){
                                    return SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->serie ?? 'A';
                                })->afterStateUpdated(function(Get $get,Set $set){
                                    $ser = $get('sel_serie');
                                    $fol = SeriesFacturas::where('id',$ser)->first();
                                    $set('serie',$fol->serie);
                                    $set('folio',$fol->folio + 1);
                                    $set('docto',$fol->serie.$fol->folio + 1);
                                }),
                        Forms\Components\Hidden::make('serie'),
                        Forms\Components\Hidden::make('folio')
                        ->default(function(){
                            return SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->folio + 1 ?? count(Facturas::all()) + 1;
                        }),
                        Forms\Components\TextInput::make('docto')
                        ->label('Documento')
                        ->required()
                        ->readOnly()
                        ->default(function(){
                            $serie = SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->serie ?? 'A';
                            $fol = SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->folio + 1 ?? count(Facturas::all()) + 1;
                            return $serie.$fol;
                        }),
                    Forms\Components\Select::make('clie')
                        ->searchable()
                        ->label('Cliente')
                        ->columnSpan(3)
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
                        ->default(fn()=>Esquemasimp::where('team_id',Filament::getTenant()->id)->first()->id)->disabledOn('edit'),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options(['MXN'=>'MXN','USD'=>'USD'])
                            ->default('MXN')->live(onBlur: true),
                    Forms\Components\TextInput::make('tcambio')
                        ->label('Tipo de Cambio')->disabled(function (Get $get) {
                            if($get('moneda') == 'MXN') return true;
                            else return false;
                        })
                        ->numeric()->default(1)->prefix('$')
                        ->currencyMask(decimalSeparator:'.',precision:4),
                    Forms\Components\TextInput::make('condiciones')
                        ->columnSpan(2)->default('CONTADO'),
                    Forms\Components\Select::make('forma')
                        ->label('Metodo de Pago')
                        ->options(Formas::all()->pluck('mostrar','clave'))
                        ->default('PPD')
                        ->columnSpan(2),
                    Forms\Components\Select::make('metodo')
                        ->label('Forma de Pago')
                        ->options(Metodos::all()->pluck('mostrar','clave'))
                        ->default('99'),
                    Forms\Components\Select::make('uso')
                        ->label('Uso de CFDI')
                        ->options(Usos::all()->pluck('mostrar','clave'))
                        ->default('G03')
                        ->columnSpan(1),
                    Forms\Components\Select::make('docto_rela')
                        ->label('Documento Relacionado')
                        ->options(Facturas::all()->pluck('docto','id'))
                        ->columnSpan(2)->live(onBlur: true),
                    Forms\Components\Select::make('tipo_rela')
                        ->label('Tipo de Relación')
                        ->options(['01'=>'01-Nota de crédito de los documentos relacionados',
                        '02'=>'02-Nota de débito de los documentos relacionados',
                        '03'=>'03-Devolución de mercancía sobre facturas o traslados previos',
                        '04'=>'04-Sustitución de los CFDI previos',
                        '05'=>'05-Traslados de mercancias facturados previamente',
                        '06'=>'06-Factura generada por los traslados previos',
                        '07'=>'07-CFDI por aplicación de anticipo'])
                        ->columnSpan(2)
                        ->disabled(function (Get $get) {
                            if($get('docto_rela') == '') return true;
                            else return false;
                        }),
                    TableRepeater::make('partidas')
                        ->relationship()
                        ->disabled(function(Get $get){
                            if($get('clie') > 0)
                                return false; else return true;
                        })
                        ->addActionLabel('Agregar')
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            // Si es una nueva factura (no tiene ID aún) y no viene de remisión
                            // Podemos intentar manejar el inventario aquí o en el afterCreate del Page
                        })
                        ->headers([
                            Header::make('Cantidad')->width('100px'),
                            Header::make('Item')->width('300px'),
                            Header::make('Unitario'),
                            Header::make('Subtotal'),
                            Header::make('Observaciones')->width('200px'),
                        ])->schema([
                            TextInput::make('cant')->numeric()
                            ->default(1)->label('Cantidad')
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
                                $retivapar = $subt * ($esq->retiva*0.01);
                                $retisrpar = $subt * ($esq->retisr*0.01);
                                $iepspar = $subt * ($esq->ieps*0.01);
                                $tot = $subt + $ivapar - $retivapar - $retisrpar + $iepspar;
                                $set('total',$tot);
                                $set('clie',$get('../../clie'));
                                Self::updateTotals($get,$set);
                            }),
                            Select::make('item')
                            ->searchable()
                            ->options(Inventario::where('team_id',Filament::getTenant()->id)->select(DB::raw("id,CONCAT(clave,'-',descripcion) as descripcion"))->pluck('descripcion','id'))
                            ->createOptionForm(function ($form) {
                                return $form
                                    ->schema([
                                        TextInput::make('clave')->label('SKU')->required(),
                                        TextInput::make('descripcion')->columnSpan(3)->required(),
                                        TextInput::make('precio')->required()->default(0)
                                        ->currencyMask(decimalSeparator:'.',precision:4),
                                        Forms\Components\TextInput::make('cvesat')
                                            ->label('Clave SAT')
                                            ->default('01010101')
                                            ->required()
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('Cat_cve_sat')
                                                    ->label('Buscador')
                                                    ->icon('fas-circle-question')
                                                    ->form([
                                                        Forms\Components\Select::make('CatCveSat')
                                                            ->default(function(Get $get): string{
                                                                if($get('cvesat'))
                                                                    $val = $get('cvesat');
                                                                else
                                                                    $val = '01010101';
                                                                return $val;
                                                            })
                                                            ->label('Claves SAT')
                                                            ->searchable()
                                                            ->searchDebounce(100)
                                                            ->getSearchResultsUsing(fn (string $search): array => Claves::where('mostrar', 'like', "%{$search}%")->limit(50)->pluck('mostrar', 'clave')->toArray())
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
                            })->live(onBlur: true)
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $cli = $get('../../clie');
                                $prod = Inventario::where('id',$get('item'))->first();
                                if($prod == null){
                                    Notification::make()->title('No existe el producto')->danger()->send();
                                    return;
                                }
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
                                Self::updateTotals($get,$set);
                            }),
                            Hidden::make('descripcion'),
                            TextInput::make('precio')
                                ->numeric()
                                ->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $cant = $get('cant');
                                    $cost = $get('precio');
                                    $subt = $cost * $cant;
                                    $set('subtotal',$subt);
                                    $ivap = $get('../../esquema');
                                    $esq = Esquemasimp::where('id',$ivap)->get();
                                    $esq = $esq[0];
                                    $set('por_imp1',$esq->iva);
                                    $set('por_imp2',$esq->retiva);
                                    $set('por_imp3',$esq->retisr);
                                    $set('por_imp4',$esq->ieps);
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
                                ->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4),
                            Hidden::make('iva'),
                            Hidden::make('retiva'),
                            Hidden::make('retisr'),
                            Hidden::make('ieps'),
                            Hidden::make('total'),
                            Hidden::make('unidad'),
                            Hidden::make('cvesat'),
                            Hidden::make('clie'),
                            TextInput::make('observa'),
                            Hidden::make('siguiente'),
                            Hidden::make('costo'),
                            Hidden::make('por_imp1'),
                            Hidden::make('por_imp2'),
                            Hidden::make('por_imp3'),
                            Hidden::make('por_imp4'),
                            Hidden::make('team_id')->default(Filament::getTenant()->id),
                        ])->columnSpan('full')->streamlined()

                    ])->grow(true)->columns(5),
                Section::make('Totales')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                        ->readOnly()->inlineLabel()
                        ->numeric()->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4),
                    Forms\Components\Hidden::make('Impuestos')
                        ->default(0.00),
                    Forms\Components\TextInput::make('iva')->inlineLabel()->label('IVA')->readOnly()->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4),
                    Forms\Components\TextInput::make('retiva')->inlineLabel()->label('Retención IVA')->readOnly()->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4),
                    Forms\Components\TextInput::make('retisr')->inlineLabel()->label('Retención ISR')->readOnly()->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4),
                    Forms\Components\TextInput::make('ieps')->inlineLabel()->label('Retención IEPS')->readOnly()->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:4),
                    Forms\Components\TextInput::make('total')
                        ->numeric()->inlineLabel()
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
                                            $set('por_imp1',$esq->iva);
                                            $set('por_imp2',$esq->retiva);
                                            $set('por_imp3',$esq->retisr);
                                            $set('por_imp4',$esq->ieps);
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
                        ActionsAction::make('Facturar Cotización')
                            ->badge()->tooltip('Facturar Cotización')
                            ->icon('fas-file-invoice')
                            ->color(Color::Green)
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitActionLabel('Grabar')
                            ->modalWidth('7xl')
                            ->mountUsing(function (Forms\ComponentContainer $form, $livewire) {
                                $cotId = $livewire->requ ?? null;
                                if(!$cotId) return;
                                $record = Cotizaciones::find($cotId);
                                if(!$record) return;

                                $partidas = CotizacionesPartidas::where('cotizaciones_id',$record->id)
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
                                    'cotizacion_id' => $record->id,
                                    'clie' => $record->clie,
                                    'nombre' => $record->nombre,
                                    'esquema' => $record->esquema,
                                    'moneda' => $record->moneda,
                                    'tcambio' => $record->tcambio,
                                    'metodo' => $record->metodo ?? 'PUE',
                                    'forma' => $record->forma ?? '01',
                                    'uso' => $record->uso ?? 'G03',
                                    'condiciones' => $record->condiciones ?? 'CONTADO',
                                    'partidas' => $partidas,
                                ]);
                            })
                            ->form([
                                Forms\Components\Section::make('Información de la Cotización')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Placeholder::make('origen_folio')
                                                    ->label('Folio Cotización')
                                                    ->content(fn ($livewire) => Cotizaciones::find($livewire->requ)?->folio),
                                                Forms\Components\Placeholder::make('origen_fecha')
                                                    ->label('Fecha')
                                                    ->content(fn ($livewire) => Cotizaciones::find($livewire->requ)?->fecha),
                                                Forms\Components\Placeholder::make('origen_cliente')
                                                    ->label('Cliente')
                                                    ->content(fn ($livewire) => Cotizaciones::find($livewire->requ)?->nombre),
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
                            ->action(function (array $data, Set $set, Get $get) {
                                $cotId = $data['cotizacion_id'] ?? null;
                                if(!$cotId) return;
                                $cot = Cotizaciones::find($cotId);
                                if(!$cot) return;

                                $set('cotizacion_id', $cot->id);
                                $set('clie', $data['clie']);
                                $set('nombre', $data['nombre']);
                                $set('esquema', $data['esquema']);
                                $set('moneda', $data['moneda']);
                                $set('tcambio', $data['tcambio']);
                                $set('metodo', $data['metodo']);
                                $set('forma', $data['forma']);
                                $set('uso', $data['uso']);
                                $set('condiciones', $data['condiciones']);
                                $set('observa', 'Generada desde Cotización #'.$cot->folio);

                                $partidas = [];
                                foreach ($data['partidas'] as $pData) {
                                    $parOriginal = CotizacionesPartidas::find($pData['partida_id']);
                                    if (!$parOriginal) continue;

                                    $cantFacturar = $pData['cantidad_a_facturar'];
                                    $factor = $parOriginal->cant > 0 ? ($cantFacturar / $parOriginal->cant) : 0;

                                    $lineSubtotal = $parOriginal->precio * $cantFacturar;
                                    $lineIva = $parOriginal->iva * $factor;
                                    $lineRetIva = $parOriginal->retiva * $factor;
                                    $lineRetIsr = $parOriginal->retisr * $factor;
                                    $lineIeps = $parOriginal->ieps * $factor;
                                    $lineTotal = $lineSubtotal + $lineIva - $lineRetIva - $lineRetIsr + $lineIeps;

                                    $partidas[] = [
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
                                        'clie' => $data['clie'],
                                        'cotizacion_partida_id' => $parOriginal->id, // We'll need to update pendientes on save
                                    ];
                                }
                                $set('partidas', $partidas);
                                Self::updateTotals2($get, $set);
                            }),
                        ActionsAction::make('Facturar Pedido')
                            ->badge()->tooltip('Facturar Pedido')
                            ->icon('fas-file-invoice')
                            ->color(Color::Green)
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitActionLabel('Grabar')
                            ->modalWidth('7xl')
                            ->mountUsing(function (Forms\ComponentContainer $form, $livewire) {
                                $pedId = $livewire->requ ?? null;
                                if(!$pedId) return;
                                $record = Pedidos::find($pedId);
                                if(!$record) return;

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
                                    'pedido_id' => $record->id,
                                    'clie' => $record->clie,
                                    'nombre' => $record->nombre,
                                    'esquema' => $record->esquema,
                                    'moneda' => $record->moneda,
                                    'tcambio' => $record->tcambio,
                                    'metodo' => $record->metodo ?? 'PUE',
                                    'forma' => $record->forma ?? '01',
                                    'uso' => $record->uso ?? 'G03',
                                    'condiciones' => $record->condiciones ?? 'CONTADO',
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
                                                    ->content(fn ($livewire) => Pedidos::find($livewire->requ)?->folio),
                                                Forms\Components\Placeholder::make('origen_fecha')
                                                    ->label('Fecha')
                                                    ->content(fn ($livewire) => Pedidos::find($livewire->requ)?->fecha),
                                                Forms\Components\Placeholder::make('origen_cliente')
                                                    ->label('Cliente')
                                                    ->content(fn ($livewire) => Pedidos::find($livewire->requ)?->nombre),
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
                            ->action(function (array $data, Set $set, Get $get) {
                                $pedId = $data['pedido_id'] ?? null;
                                if(!$pedId) return;
                                $ped = Pedidos::find($pedId);
                                if(!$ped) return;

                                $set('pedido_id', $ped->id);
                                $set('clie', $data['clie']);
                                $set('nombre', $data['nombre']);
                                $set('esquema', $data['esquema']);
                                $set('moneda', $data['moneda']);
                                $set('tcambio', $data['tcambio']);
                                $set('metodo', $data['metodo']);
                                $set('forma', $data['forma']);
                                $set('uso', $data['uso']);
                                $set('condiciones', $data['condiciones']);
                                $set('observa', 'Generada desde Pedido #'.$ped->folio);

                                $partidas = [];
                                foreach ($data['partidas'] as $pData) {
                                    $parOriginal = PedidosPartidas::find($pData['partida_id']);
                                    if (!$parOriginal) continue;

                                    $cantFacturar = $pData['cantidad_a_facturar'];
                                    $factor = $parOriginal->cant > 0 ? ($cantFacturar / $parOriginal->cant) : 0;

                                    $lineSubtotal = $parOriginal->precio * $cantFacturar;
                                    $lineIva = $parOriginal->iva * $factor;
                                    $lineRetIva = $parOriginal->retiva * $factor;
                                    $lineRetIsr = $parOriginal->retisr * $factor;
                                    $lineIeps = $parOriginal->ieps * $factor;
                                    $lineTotal = $lineSubtotal + $lineIva - $lineRetIva - $lineRetIsr + $lineIeps;

                                    $partidas[] = [
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
                                        'clie' => $data['clie'],
                                        'pedido_partida_id' => $parOriginal->id,
                                    ];
                                }
                                $set('partidas', $partidas);
                                Self::updateTotals2($get, $set);
                            }),
                        ActionsAction::make('Enlazar Nota')
                            ->badge()->tooltip('Enlazar Nota de Venta')
                            ->icon('fas-file-import')
                            ->modalCancelActionLabel('Cerrar')
                            ->modalSubmitActionLabel('Seleccionar')
                            ->form([
                                Select::make('OrdenC')
                                ->searchable()
                                ->label('Seleccionar Nota')
                                ->options(
                                    Notasventa::whereIn('estado',['Activa','Parcial'])
                                    ->select(DB::raw("concat('Folio: ',folio,' Fecha: ',fecha,' Proveedor: ',nombre,' Importe: ',total) as Orden"),'id')
                                    ->pluck('Orden','id'))
                            ])->action(function(Get $get,Set $set,$data){
                                $selorden = $data['OrdenC'];
                                $set('orden',$selorden);
                                $orden = Notasventa::where('id',$data['OrdenC'])->get();
                                $Opartidas = NotasventaPartidas::where('notasventa_id',$data['OrdenC'])->get();
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
            Forms\Components\Hidden::make('cotizacion_id')->dehydrated(true),
            Forms\Components\Hidden::make('pedido_id')->dehydrated(true),
            Forms\Components\Hidden::make('nombre'),
            Forms\Components\Hidden::make('estado')->default('Activa'),
            Forms\Components\Textarea::make('observa')
                ->columnSpanFull()->label('Observaciones')
                ->rows(3),
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
            ->modifyQueryUsing(function ($query) {
                return $query->OrderBy('fecha','desc')
                ->OrderBy('folio','desc');
            })
        ->columns([
            Tables\Columns\TextColumn::make('docto')
                ->label('Factura')
                ->numeric()
                ->sortable()->width('20%')->searchable(),
            Tables\Columns\TextColumn::make('fecha')
                ->date('d-m-Y')
                ->sortable()->width('20%'),
            Tables\Columns\TextColumn::make('nombre')
                ->searchable()
                ->label('Cliente')->width('30%'),
            Tables\Columns\TextColumn::make('subtotal')
                ->numeric()
                ->sortable()
                ->currency('USD',true)->width('10%'),
            Tables\Columns\TextColumn::make('iva')
                ->numeric()
                ->sortable()
                ->currency('USD',true)->width('10%'),
            Tables\Columns\TextColumn::make('total')
                ->numeric()
                ->sortable()
                ->currency('USD',true)->width('10%'),
            Tables\Columns\TextColumn::make('estado')
                ->searchable()
            ->formatStateUsing(function ($record){
                if($record->estado == 'Activa') return new HtmlString('<span class="badge badge-error">No Timbrada</span>');
                else return $record->estado;
            }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                ->label('Consultar')->icon('fas-eye')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('full'),
                Action::make('Imprimir')->icon('fas-print')
                ->disabled(fn($record)=>$record->estado != 'Timbrada')
                ->action(function($record){
                    //self::DescargaPdf($record);
                    $emp = Team::where('id',$record->team_id)->first();
                    $cli = Clientes::where('id',$record->clie)->first();
                    $archivo_pdf = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                    $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                    if(File::exists($ruta))File::delete($ruta);
                    $data = ['idorden'=>$record->id,'id_empresa'=>Filament::getTenant()->id];
                    $html = View::make('RepFactura',$data)->render();
                    Browsershot::html($html)->format('Letter')
                        ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                        ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                        ->noSandbox()
                        ->scale(0.8)->savePdf($ruta);
                    return response()->download($ruta);
                }),
                Action::make('Cancelar')
                    ->icon('fas-ban')
                    ->form([
                        Select::make('motivo')
                        ->label('Motivo')->options([
                            '01'=>'01 - Comprobante emitido con errores con relación',
                            '02'=>'02 - Comprobante emitido con errores sin relación',
                            '03'=>'03 - No se llevó a cabo la operación ',
                            '04'=>'04 - Operación nominativa relacionada en una factura global'
                        ])->live(onBlur: true)
                        ->default('02'),
                        Select::make('Folio')
                        ->disabled(fn(Get $get) => $get('motivo') != '01')
                        ->label('Folio Sustituye')->options(
                            Facturas::where('estado','Timbrada')
                                ->where('team_id',Filament::getTenant()->id)
                                ->select(DB::raw("concat(serie,folio) as Folio,uuid"))
                                ->pluck('Folio','uuid')
                            )
                    ])
                    ->action(function(Model $record,$data){
                        $est = $record->estado;
                        $factura = $record->id;
                        $receptor = $record->clie;
                        $folio = $data['Folio'] ?? null;
                        if($est == 'Activa'||$est == 'Timbrada')
                        {

                            if($est == 'Timbrada')
                            {
                                $res = app(TimbradoController::class)->CancelarFactura($factura, $receptor,$data['motivo'],$folio);
                                $resultado = json_decode($res);
                                CuentasCobrar::where('refer',$record->id)->delete();
                                Clientes::where('id',$record->clie)->decrement('saldo', ($record->total*$record->tcambio));
                                if($resultado->codigo == 201){
                                    Facturas::where('id',$record->id)->update([
                                        'fecha_cancela'=>Carbon::now(),
                                        'motivo'=>$data['motivo'],
                                        'sustituye'=>$folio,
                                        'xml_cancela'=>$resultado->acuse,
                                    ]);
                                    Notification::make()
                                        ->title($resultado->mensaje)
                                        ->success()
                                        ->send();
                                }else{
                                    Notification::make()
                                        ->title($resultado->mensaje)
                                        ->warning()
                                        ->send();
                                }
                            }
                            Facturas::where('id',$record->id)->update([
                                'estado'=>'Cancelada'
                            ]);
                            CuentasCobrar::where('documento',$record->docto)
                                ->where('team_id',Filament::getTenant()->id)->delete();
                            Clientes::where('id',$record->clie)->decrement('saldo', $record->total);

                            $par_cot = FacturasPartidas::where('facturas_id',$record->id)->get();
                            foreach ($par_cot as $partida) {
                                CotizacionesPartidas::where('id',$partida->cotizacion_partida_id)
                                    ->increment('pendientes',$partida->cant);
                            }
                            Cotizaciones::where('id',$record->cotizacion_id)
                                ->update(['estado'=>'Activa']);
                            $par_ped = FacturasPartidas::where('facturas_id',$record->id)->get();
                            foreach ($par_ped as $partid) {
                                PedidosPartidas::where('id',$partid->pedido_partida_id)
                                    ->increment('pendientes',$partid->cant);
                            }
                            Pedidos::where('id',$record->pedido_id)
                                ->update(['estado'=>'Activa']);

                            Notification::make()
                                ->title('Factura Cancelada')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('Consultar Estado')
                    ->icon('fas-arrows-to-eye')
                    ->action(function(Model $record){
                        $factura = $record->id;
                        $receptor = $record->clie;
                        $res = app(TimbradoController::class)->ConsultarFacturaSAT($factura, $receptor);
                        $resultado = json_decode($res);
                        Notification::make()
                            ->title($resultado->{'Codigo del SAT'})
                            ->success()
                            ->send();
                    }),
                Action::make('Descargar XML')
                    ->label('Descargar XML')
                    ->icon('fas-download')
                    ->disabled(function($record){
                        if($record->estado == 'Timbrada'||$record->estado == 'Cancelada') return false;
                        else return true;
                    })
                    ->action(function($record){
                        $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                        $cli = Clientes::where('id',$record->clie)->first();
                        $nombre = $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.xml';
                        $archivo = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;
                        if(File::exists($archivo)) unlink($archivo);
                        $xml = $record->xml;
                        $xml = Cleaner::staticClean($xml);
                        File::put($archivo,$xml);
                        $ruta = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;

                        return response()->download($ruta);
                    }),
                Action::make('Timbrar')->icon('fas-bell-concierge')
                    ->label('Timbrar Factura')
                    ->requiresConfirmation()
                    ->disabled(function($record){
                        if($record->estado == 'Timbrada'||$record->estado == 'Cancelada') return true;
                        else return false;
                    })->action(function($record) {
                        $data = $record;
                        $factura = $data->id;
                        $receptor = $data->clie;
                        $emp = DatosFiscales::where('team_id',Filament::getTenant()->id)->first();
                        if ($emp->cer != null && $emp->cer != '') {
                            $res = app(TimbradoController::class)->TimbrarFactura($factura, $receptor);
                            $resultado = json_decode($res);
                            $codigores = $resultado->codigo;
                            if ($codigores == "200") {
                                $date = Carbon::now();
                                $facturamodel = Facturas::find($factura);
                                $facturamodel->timbrado = 'SI';
                                $facturamodel->xml = $resultado->cfdi;
                                $facturamodel->fecha_tim = $date;
                                $facturamodel->save();
                                $res2 = app(TimbradoController::class)->actualiza_fac_tim($factura, $resultado->cfdi, "F");
                                $mensaje_graba = 'Factura Timbrada Se genero el CFDI UUID: ' . $res2;
                                $cliente_d = Clientes::where('id',$record->clie)->first();
                                $dias_cr = intval($cliente_d?->dias_credito ?? 0);
                                CuentasCobrar::create([
                                    'cliente'=>$record->clie,
                                    'concepto'=>1,
                                    'descripcion'=>'Factura',
                                    'documento'=>$record->serie.$record->folio,
                                    'fecha'=>Carbon::now(),
                                    'vencimiento'=>Carbon::now()->addDays($dias_cr),
                                    'importe'=>$record->total * $record->tcambio,
                                    'saldo'=>$record->total * $record->tcambio,
                                    'team_id'=>Filament::getTenant()->id,
                                    'refer'=>$record->id
                                ]);
                                Notification::make()
                                    ->success()
                                    ->title('Factura Timbrada Correctamente')
                                    ->body($mensaje_graba)
                                    ->duration(2000)
                                    ->send();
                            } else {
                                $mensaje_tipo = "2";
                                $mensaje_graba = $resultado->mensaje;
                                $record->error_timbrado = $resultado->mensaje;
                                $record->save();
                                Notification::make()
                                    ->warning()
                                    ->title('Error al Timbrar el Documento')
                                    ->body($mensaje_graba)
                                    ->persistent()
                                    ->send();
                            }
                        }else{
                            Notification::make()
                                ->warning()
                                ->title('Error al Timbrar el Documento')
                                ->body('No existen Sellos Digitales Validos')
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('Copiar')
                    ->icon('fas-copy')
                    ->label('Copiar Factura')
                    ->requiresConfirmation()
                    ->action(function(Model $record){
                        DB::transaction(function () use ($record) {
                            $teamId = Filament::getTenant()->id;
                            $serieRow = SeriesFacturas::where('team_id', $teamId)->where('tipo', 'F')->lockForUpdate()->first();
                            $serie = $serieRow->serie ?? 'A';
                            $nuevoFolio = ($serieRow->folio ?? 0) + 1;

                            // Crear encabezado de factura copiada
                            $nueva = new Facturas();
                            $nueva->team_id = $teamId;
                            $nueva->serie = $serie;
                            $nueva->folio = $nuevoFolio;
                            $nueva->docto = $serie . $nuevoFolio;
                            $nueva->fecha = Carbon::now();
                            $nueva->clie = $record->clie;
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
                            $nueva->metodo = $record->metodo;
                            $nueva->forma = $record->forma;
                            $nueva->uso = $record->uso;
                            $nueva->condiciones = $record->condiciones;
                            $nueva->vendedor = $record->vendedor;
                            $nueva->moneda = $record->moneda;
                            $nueva->tcambio = $record->tcambio;
                            $nueva->pendiente_pago = $record->total; // nuevo saldo pendiente
                            // Campos que no se deben copiar tal cual (timbrado / CFDI)
                            $nueva->uuid = null;
                            $nueva->timbrado = null;
                            $nueva->xml = null;
                            $nueva->fecha_tim = null;
                            $nueva->fecha_cancela = null;
                            $nueva->motivo = null;
                            $nueva->sustituye = null;
                            $nueva->xml_cancela = null;
                            $nueva->error_timbrado = null;
                            $nueva->save();

                            // Duplicar partidas
                            $partidas = FacturasPartidas::where('facturas_id', $record->id)->get();
                            foreach ($partidas as $par) {
                                FacturasPartidas::create([
                                    'facturas_id' => $nueva->id,
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
                                    'clie' => $par->clie,
                                    'observa' => $par->observa,
                                    'anterior' => $par->anterior,
                                    'siguiente' => $par->siguiente,
                                    'por_imp1' => $par->por_imp1,
                                    'por_imp2' => $par->por_imp2,
                                    'por_imp3' => $par->por_imp3,
                                    'por_imp4' => $par->por_imp4,
                                    'team_id' => $teamId,
                                ]);
                            }

                            // Incrementar folio de la serie utilizada
                            if ($serieRow) {
                                $serieRow->folio = $nuevoFolio;
                                $serieRow->save();
                            }

                            Notification::make()
                                ->title('Factura copiada correctamente: ' . $nueva->docto)
                                ->success()
                                ->send();
                        });
                    }),
                Action::make('Enviar por Correo')
                ->icon('fas-envelope')
                ->action(function($record,$livewire){
                    $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                    $cli = Clientes::where('id',$record->clie)->first();
                    $nombrepdf = $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                    $nombrexml = $emp->rfc.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.xml';
                    $filepdf = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombrepdf;
                    $filexml = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombrexml;
                    if(File::exists($filepdf)) unlink($filepdf);
                    Pdf::loadView('RepFactura',['idorden'=>$record->id,'id_empresa'=>Filament::getTenant()->id])
                        ->save($filepdf);
                    if(File::exists($filexml)) unlink($filexml);
                    $xml = $record->xml;
                    $xml = Cleaner::staticClean($xml);
                    File::put($filexml,$xml);
                    $mailConf = Mailconfig::where('team_id',Filament::getTenant()->id)->first();
                    $Cliente = Clientes::where('id',$record->clie)->first();
                    $mail = new PHPMailer();
                    $mail->isSMTP();
                    //$mail->SMTPDebug = 2;
                    $mail->Host = 'smtp.ionos.mx';
                    $mail->Port = 587;
                    $mail->AuthType = 'LOGIN';
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure='tls';
                    $mail->Username = 'sistema@app-tusimpuestos.com';
                    $mail->Password = '*TusImpuestos2025$*';
                    $mail->setFrom('sistema@app-tusimpuestos.com', Filament::getTenant()->name);
                    $mail->addAddress($Cliente->correo, $Cliente->nombre);
                    $mail->addAttachment($filepdf,$filepdf);
                    $mail->addAttachment($filexml,$filexml);
                    $mail->Subject = 'Factura CFDI '.$record->docto.' '.$Cliente->nombre;
                    $mail->msgHTML('<b>Factura CFDI</b>');
                    $mail->Body = 'Factura CFDI';
                    $mail->send();
                    Notification::make()
                        ->success()
                        ->title('Envio de Correo')
                        ->body('Factura Enviada '.$mail->ErrorInfo)
                        ->send();
                }),
                    Action::make('Ver Error')
                        ->icon('fas-exclamation-triangle')
                        ->visible(fn($record)=>$record->estado == 'Activa')
                        ->form(function (Form $form,$record) {
                            return $form
                                ->schema([
                                    Forms\Components\Textarea::make('error_timbrado')
                                        ->label('Error Timbrado')->default($record->error_timbrado)
                                        ->readOnly()
                                ]);
                        })->modalWidth('7xl')
                        ->modalSubmitAction(false)
                ]),
                Tables\Actions\EditAction::make('editar')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->modalWidth('full')
                    ->visible(function ($record){
                        if($record->estado == 'Activa') return true;
                        else return false;
                    })
                    ->after(function($record,$livewire){
                        $partidas = $record->partidas;
                        $nopar = 0;
                        $esq = Esquemasimp::where('id',$record->esquema)->first();
                        $imp1 = $esq->iva * 0.01;
                        $imp2 = $esq->retiva * 0.01;
                        $imp3 = $esq->retisr * 0.01;
                        $imp4 = $esq->ieps * 0.01;
                        foreach($partidas as $partida)
                        {
                            $partida->iva = $partida->subtotal * $imp1;
                            $partida->retiva = $partida->subtotal * $imp2;
                            $partida->retisr = $partida->subtotal * $imp3;
                            $partida->ieps = $partida->subtotal * $imp4;
                            $partida->save();
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
                        Clientes::where('id',$record->clie)->increment('saldo', $record->total);
                        $cliente_d = Clientes::where('id',$record->clie)->first();

                        $record->pendiente_pago = $record->total;
                        $record->save();
                        if($record->docto_rela != ''){
                            DoctosRelacionados::create([
                                'docto_type'=>'F',
                                'docto_id'=>$record->id,
                                'rel_id'=>$record->docto_rela,
                                'rel_type'=>'F',
                                'rel_cause'=>$record->tipo_rela,
                                'team_id'=>Filament::getTenant()->id
                            ]);
                        }
                        //-----------------------------
                        $data = $record;
                        $factura = $data->id;
                        $receptor = $data->clie;
                        $emp = Team::where('id',Filament::getTenant()->id)->first();

                        if($emp->archivokey != null&&$emp->archivokey != '')
                        {
                            $res = app(TimbradoController::class)->TimbrarFactura($factura, $receptor);
                            $resultado = json_decode($res);
                            $codigores = $resultado->codigo;
                            if ($codigores == "200") {
                                $date = Carbon::now();
                                $facturamodel = Facturas::find($factura);
                                $facturamodel->timbrado = 'SI';
                                $facturamodel->xml = $resultado->cfdi;
                                $facturamodel->fecha_tim = $date;
                                $facturamodel->save();
                                $res2 = app(TimbradoController::class)->actualiza_fac_tim($factura, $resultado->cfdi, "F");
                                $mensaje_graba = 'Factura Timbrada Se genero el CFDI UUID: ' . $res2;
                                $dias_cr = intval($cliente_d?->dias_credito ?? 0);
                                CuentasCobrar::create([
                                    'cliente'=>$record->clie,
                                    'concepto'=>1,
                                    'descripcion'=>'Factura',
                                    'documento'=>$record->serie.$record->folio,
                                    'fecha'=>Carbon::now(),
                                    'vencimiento'=>Carbon::now()->addDays($dias_cr),
                                    'importe'=>$record->total * $record->tcambio,
                                    'saldo'=>$record->total * $record->tcambio,
                                    'team_id'=>Filament::getTenant()->id,
                                    'refer'=>$record->id
                                ]);
                                $emp = Team::where('id',Filament::getTenant()->id)->first();
                                $cli = Clientes::where('id',$record->clie)->first();
                                $archivo_pdf = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                                $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                if(File::exists($ruta))File::delete($ruta);
                                $data = ['idorden'=>$record->id,'id_empresa'=>Filament::getTenant()->id];
                                $html = View::make('RepFactura',$data)->render();
                                Browsershot::html($html)->format('Letter')
                                    ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                    ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                    ->noSandbox()
                                    ->scale(0.8)->savePdf($ruta);
                                $nombre = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.xml';
                                $archivo_xml = public_path().'/TMPCFDI/'.$nombre;
                                if(File::exists($archivo_xml)) unlink($archivo_xml);
                                $xml = $resultado->cfdi;
                                $xml = Cleaner::staticClean($xml);
                                File::put($archivo_xml,$xml);
                                //-----------------------------------------------------------
                                $zip = new \ZipArchive();
                                $zipPath = public_path().'/TMPCFDI/';
                                $zipFileName = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.zip';
                                $zipFile = $zipPath.$zipFileName;
                                if ($zip->open(($zipFile), \ZipArchive::CREATE) === true) {
                                    $zip->addFile($archivo_xml, $nombre);
                                    $zip->addFile($ruta, $archivo_pdf);
                                    $zip->close();
                                }else{
                                    return false;
                                }
                                $docto = $record->serie.$record->folio;
                                self::EnvioCorreo($record->clie,$ruta,$archivo_xml,$docto,$archivo_pdf,$nombre);
                                self::MsjTimbrado($mensaje_graba);
                                return response()->download($zipFile);
                                //-----------------------------------------------------------

                            } else {
                                $mensaje_tipo = "2";
                                $mensaje_graba = $resultado->mensaje;
                                $record->error_timbrado = $resultado->mensaje;
                                $record->save();
                                Notification::make()
                                    ->warning()
                                    ->title('Error al Timbrar el Documento')
                                    ->body($mensaje_graba)
                                    ->persistent()
                                    ->send();
                            }
                            //self::DescargaPdf($record);

                        }
                        //------------------------------------------

                    })
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nueva Factura')
                ->label('Agregar')->icon('fas-circle-plus')
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('full')
                ->before(function ($record,$data){
                    $ser = intval($data['sel_serie']);
                    SeriesFacturas::where('id',$ser)->increment('folio',1);
                })
                ->after(function($record,$livewire){
                    $partidas = $record->partidas;
                    $nopar = 0;
                    $esq = Esquemasimp::where('id',$record->esquema)->first();
                    $imp1 = $esq->iva * 0.01;
                    $imp2 = $esq->retiva * 0.01;
                    $imp3 = $esq->retisr * 0.01;
                    $imp4 = $esq->ieps * 0.01;
                    $cliente_d = Clientes::where('id',$record->clie)->first();
                    $record->pendiente_pago = $record->total;
                    $record->save();

                    // Actualizar pendientes si viene de Cotización o Pedido
                    $factura_partidas = FacturasPartidas::where('facturas_id',$record->id)->get();
                    foreach($factura_partidas as $fpartida) {
                        if ($fpartida->cotizacion_partida_id) {
                            $cpartida = CotizacionesPartidas::find($fpartida->cotizacion_partida_id);
                            if ($cpartida) {
                                $cpartida->decrement('pendientes', $fpartida->cant);
                            }
                        }
                        if ($fpartida->pedido_partida_id) {
                            $ppartida = PedidosPartidas::find($fpartida->pedido_partida_id);
                            if ($ppartida) {
                                $ppartida->decrement('pendientes', $fpartida->cant);
                            }
                        }
                    }

                    if ($record->cotizacion_id) {
                        $pendientesTotales = CotizacionesPartidas::where('cotizaciones_id', $record->cotizacion_id)->sum('pendientes');
                        $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial';
                        Cotizaciones::where('id', $record->cotizacion_id)->update(['estado' => $nuevoEstado]);
                    }
                    if ($record->pedido_id) {
                        $pendientesTotales = PedidosPartidas::where('pedidos_id', $record->pedido_id)->sum('pendientes');
                        $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrado' : 'Parcial';
                        Pedidos::where('id', $record->pedido_id)->update(['estado' => $nuevoEstado]);
                    }

                    if($record->docto_rela != ''){
                        DoctosRelacionados::create([
                            'docto_type'=>'F',
                            'docto_id'=>$record->id,
                            'rel_id'=>$record->docto_rela,
                            'rel_type'=>'F',
                            'rel_cause'=>$record->tipo_rela,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                    }
                    //-----------------------------
                        $data = $record;
                        $factura = $data->id;
                        $receptor = $data->clie;
                        $emp = Team::where('id',Filament::getTenant()->id)->first();

                        if($emp->archivokey != null&&$emp->archivokey != '')
                        {
                            $res = app(TimbradoController::class)->TimbrarFactura($factura, $receptor);
                            $resultado = json_decode($res);
                            $codigores = $resultado->codigo;
                            if ($codigores == "200") {
                                $date = Carbon::now();
                                $facturamodel = Facturas::find($factura);
                                $facturamodel->timbrado = 'SI';
                                $facturamodel->xml = $resultado->cfdi;
                                $facturamodel->fecha_tim = $date;
                                $facturamodel->save();
                                $res2 = app(TimbradoController::class)->actualiza_fac_tim($factura, $resultado->cfdi, "F");
                                $mensaje_graba = 'Factura Timbrada Se genero el CFDI UUID: ' . $res2;
                                $dias_cr = intval($cliente_d?->dias_credito ?? 0);
                                $emp = Team::where('id',Filament::getTenant()->id)->first();
                                $cli = Clientes::where('id',$record->clie)->first();
                                $archivo_pdf = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.pdf';
                                $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                if(File::exists($ruta))File::delete($ruta);
                                $data = ['idorden'=>$record->id,'id_empresa'=>Filament::getTenant()->id];
                                $html = View::make('RepFactura',$data)->render();
                                Browsershot::html($html)->format('Letter')
                                    ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                    ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                    ->noSandbox()
                                    ->scale(0.8)->savePdf($ruta);
                                $nombre = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.xml';
                                $archivo_xml = public_path().'/TMPCFDI/'.$nombre;
                                if(File::exists($archivo_xml)) unlink($archivo_xml);
                                $xml = $resultado->cfdi;
                                $xml = Cleaner::staticClean($xml);
                                File::put($archivo_xml,$xml);
                                //-----------------------------------------------------------
                                $zip = new \ZipArchive();
                                $zipPath = public_path().'/TMPCFDI/';
                                $zipFileName = $emp->taxid.'_FACTURA_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.zip';
                                $zipFile = $zipPath.$zipFileName;
                                if ($zip->open(($zipFile), \ZipArchive::CREATE) === true) {
                                    $zip->addFile($archivo_xml, $nombre);
                                    $zip->addFile($ruta, $archivo_pdf);
                                    $zip->close();
                                }else{
                                    return false;
                                }
                                $docto = $record->serie.$record->folio;
                                self::EnvioCorreo($record->clie,$ruta,$archivo_xml,$docto,$archivo_pdf,$nombre);
                                self::MsjTimbrado($mensaje_graba);
                                return response()->download($zipFile);
                                //-----------------------------------------------------------

                            } else {
                                $mensaje_tipo = "2";
                                $mensaje_graba = $resultado->mensaje;
                                $record->error_timbrado = $resultado->mensaje;
                                $record->save();
                                Notification::make()
                                    ->warning()
                                    ->title('Error al Timbrar el Documento')
                                    ->body($mensaje_graba)
                                    ->persistent()
                                    ->send();
                            }
                            //self::DescargaPdf($record);
                            $par_sur = FacturasPartidas::where('facturas_id',$record->id)->get();
                            foreach($par_sur as $par) {
                                SurtidoInve::create([
                                    'factura_id'=>$record->id,
                                    'factura_partida_id'=>$par->id,
                                    'item_id'=>$par->item,
                                    'descr'=>$par->descripcion,
                                    'cant'=>$par->cant,
                                    'precio_u'=>$par->precio,
                                    'costo_u'=>$par->costo,
                                    'precio_total'=>$par->subtotal,
                                    'costo_total'=>$par->costo*$par->cant,
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                            }
                        }
                    //------------------------------------------

                }),
                Action::make('Importar Facturas')
                ->icon('fas-upload')
                ->form(function (Form $form) {
                    return $form
                        ->schema([
                            FileUpload::make('Archivos')
                                ->label('Archivos XML')
                                ->directory('ImportXMLFiles')
                                ->multiple()->acceptedFileTypes(['text/xml'])

                        ]);
                })
                ->action(function($data){
                    $team  = Filament::getTenant()->id;
                    $taxid = Filament::getTenant()->taxid;
                    $archivos = $data['Archivos'];
                    foreach($archivos as $file)
                    {
                        $file = $_SERVER["DOCUMENT_ROOT"].'/storage/'.$file;
                        $xmlContents = \file_get_contents($file);
                        $cfdiData = \CfdiUtils\Cfdi::newFromString($xmlContents);
                        $comprobante = $cfdiData->getQuickReader();
                        $emisor = $comprobante->emisor;
                        $receptor = $comprobante->receptor;
                        $conceptos = $comprobante->conceptos;
                        $impuestos = $comprobante->impuestos;
                        $tfd = $comprobante->complemento->TimbreFiscalDigital;
                        $subtotal = $comprobante['subtotal'];
                        $iva = $impuestos->Traslados->Traslado['Importe'];
                        $retiva = $impuestos->Retenciones->Retencion['Importe'] ?? 0;
                        $total = $comprobante['total'];
                        $tipocom = $comprobante['TipoDeComprobante'];
                        //$pagoscom = $comprobante->complemento->Pagos;
                        //dd($tipocom);
                        if($tipocom == 'I') {
                            $subtotal = floatval($comprobante['SubTotal']);
                            $descuento = floatval($comprobante['Descuento']);
                            if(isset($impuestos['TotalImpuestosTrasladados']))$traslado = floatval($impuestos['TotalImpuestosTrasladados']);
                            if(isset($impuestos['TotalImpuestosRetenidos'])) $retencion = floatval($impuestos['TotalImpuestosRetenidos']);
                            $total = floatval($comprobante['Total']);
                            $tipocambio = floatval($comprobante['TipoCambio']);
                            $cliente = Clientes::where('rfc',$receptor['Rfc'])->first();
                            if($cliente == null) {
                                $cve = Clientes::where('team_id',Filament::getTenant()->id)
                                        ->max('id') + 1;
                                $cliente = Clientes::firstOrCreate([
                                    'clave'=>$cve,
                                    'rfc'=>$receptor['Rfc'],
                                    'nombre' => $receptor['Nombre'],
                                    'team_id'=>Filament::getTenant()->id
                                ]);
                            }
                            $factura = Facturas::firstOrCreate([
                                'serie' => $comprobante['serie'],
                                'folio' => $comprobante['folio'],
                                'docto' => $comprobante['serie'] . $comprobante['folio'],
                                'fecha' => $comprobante['fecha'],
                                'clie' => $cliente->id,
                                'nombre' => $receptor['Nombre'],
                                'esquema' => Esquemasimp::where('team_id', Filament::getTenant()->id)->first()->id,
                                'subtotal' => $subtotal,
                                'iva' => $iva,
                                'retiva' => $retiva,
                                'retisr' => 0,
                                'ieps' => 0,
                                'total' => $total,
                                'observa' => '',
                                'estado' => 'Timbrada',
                                'metodo' => $comprobante['FormaPago'],
                                'forma' => $comprobante['MetodoPago'],
                                'uso' => $receptor['UsoCFDI'],
                                'uuid' => $tfd['UUID'],
                                'condiciones' => $comprobante['CondicionesDePago'],
                                'timbrado' => 'SI',
                                'xml' => $xmlContents,
                                'fecha_tim' => $comprobante['fecha'],
                                'moneda' => $comprobante['Moneda'],
                                'tcambio' => $tipocambio,
                                'pendiente_pago' => $total,
                                'team_id' => Filament::getTenant()->id
                            ]);
                            foreach ($conceptos() as $concepto) {
                                $producto = Inventario::where('descripcion', $concepto['Descripcion'])->first();
                                if ($producto == null) {
                                    $cve = Inventario::where('team_id', Filament::getTenant()->id)
                                            ->max('id') + 1;
                                    $producto = Inventario::firstOrCreate([
                                        'clave' => $cve,
                                        'descripcion'=> $concepto['Descripcion'],
                                        'team_id' => Filament::getTenant()->id,
                                    ]);
                                }
                                FacturasPartidas::firstOrCreate([
                                    'facturas_id' => $factura->id,
                                    'item' => $producto->id,
                                    'descripcion' => $concepto['Descripcion'],
                                    'cant' => $concepto['Cantidad'],
                                    'precio' => $concepto['ValorUnitario'],
                                    'subtotal' => $concepto['Importe'],
                                    'iva' => $concepto->Impuestos->Traslados->Traslado['Importe'],
                                    'retiva' => $concepto->Impuestos->Retenciones->Retencion['Importe'] ?? 0,
                                    'retisr' => 0,
                                    'ieps' => 0,
                                    'total' => floatval($concepto['Importe']) + floatval($concepto->Impuestos->Traslados->Traslado['Importe']),
                                    'unidad' => $concepto['ClaveUnidad'],
                                    'cvesat' => $concepto['ClaveProdServ'],
                                    'costo' => 0,
                                    'clie' => $cliente->id,
                                    'por_imp1' => 16,
                                    'por_imp2' => 0,
                                    'por_imp3' => 0,
                                    'por_imp4' => 0,
                                    'team_id' => Filament::getTenant()->id
                                ]);
                            }
                        }
                    }
                    Notification::make()->title('Proceso Terminado')->success()->send();
                }),
                Action::make('Actualizar Facturas')
                ->icon('fas-sync')
                ->visible(false)
                ->action(function(){
                    $facturas = DB::table('facturas')
                        ->where('team_id','>',0)
                        ->where('estado','Timbrada')
                        ->get();

                    //$facturas = Facturas::all();
                    $recs = 0;
                    foreach ($facturas as $factura)
                    {
                        $cfd = $factura->xml ?? 'NE';
                        if($cfd != 'NE') {
                            $cfd_i = Cleaner::staticClean($cfd);
                            $cfdi = \CfdiUtils\Cfdi::newFromString($cfd_i);
                            $comprobante = $cfdi->getQuickReader();
                            $serie = $comprobante['serie'] ?? '';
                            $receptor = $comprobante->Receptor;
                            $nombre = $receptor['Nombre'];
                            DB::table('facturas')->where('id',$factura->id)->update([
                                'nombre' => $nombre
                            ]);
                            $recs ++;
                        }
                    }
                    Notification::make()->title('Proceso Terminado '.$recs.' Procesados')->success()->send();
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
                Action::make('Importar Ped')
                    ->label('Importar Pedido')
                    ->visible(false)
                    ->icon('fas-file-import')
                    ->form(function (Forms\ComponentContainer $form) {
                        return $form->schema([
                            Select::make('sel_pedido')->label('Pedido')
                                ->options(
                                    Pedidos::whereIn('estado',['Activa','Parcial'])
                                        ->where('team_id', Filament::getTenant()->id)
                                        ->get()->pluck('folio','id')
                                )
                        ]);
                    })
                    ->action(function($data,$livewire){
                        $livewire->requ = $data['sel_pedido'];
                        $livewire->getAction('Facturar Pedido')->visible(true);
                        $livewire->replaceMountedAction('Facturar Pedido');
                    }),
            ],HeaderActionsPosition::Bottom);
    }

    public static function MsjTimbrado($mensaje_graba)
    {
        Notification::make()
            ->success()
            ->title('Factura Timbrada Correctamente')
            ->body($mensaje_graba)
            ->duration(2000)
            ->send();
    }
    public static function EnvioCorreo($cliente,$filepdf,$filexml,$docto,$nombrepdf,$nombrexml)
    {
        $Cliente = Clientes::where('id',$cliente)->first();
        if($Cliente->correo != null) {
            $mail = new PHPMailer();
            $mail->isSMTP();
            //$mail->SMTPDebug = 2;
            $mail->Host = 'smtp.ionos.mx';
            $mail->Port = 587;
            $mail->AuthType = 'LOGIN';
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Username = 'sistema@app-tusimpuestos.com';
            $mail->Password = '*TusImpuestos2025$*';
            $mail->setFrom('sistema@app-tusimpuestos.com', Filament::getTenant()->name);
            $mail->addAddress($Cliente->correo, $Cliente->nombre);
            $mail->addAttachment($filepdf, $nombrepdf);
            $mail->addAttachment($filexml, $nombrexml);
            $mail->Subject = 'Factura CFDI ' . $docto . ' ' . $Cliente->nombre;
            $mail->msgHTML('<b>Factura CFDI</b>');
            $mail->Body = 'Factura CFDI';
            $mail->send();
            Notification::make()
                ->success()
                ->title('Envio de Correo')
                ->body('Factura Enviada ' . $mail->ErrorInfo)
                ->send();
        }else{
            Notification::make()
                ->warning()
                ->title('Envio de Correo')
                ->body('Cliente sin Correo configurado')
                ->send();
        }
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
            'index' => Pages\ListFacturas::route('/'),
            //'create' => Pages\CreateFacturas::route('/create'),
            //'edit' => Pages\EditFacturas::route('/{record}/edit'),
        ];
    }
}
