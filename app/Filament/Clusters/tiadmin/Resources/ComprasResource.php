<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\ComprasResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\OrdenesResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\CatCuentas;
use App\Models\Compras;
use App\Models\ComprasPartidas;
use App\Models\CuentasPagar;
use App\Models\Esquemasimp;
use App\Models\Inventario;
use App\Models\Movinventario;
use App\Models\Ordenes;
use App\Models\OrdenesPartidas;
use App\Models\Proveedores;
use App\Models\Proyectos;
use App\Models\Requisiciones;
use App\Models\Terceros;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
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
    protected static ?int $navigationSort = 2;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras']);
    }
    protected static ?string $navigationIcon = 'fas-cart-plus';
    protected static ?string $label = 'Recepción';
    protected static ?string $pluralLabel = 'Recepciones';

    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';

    public static function form(Form $form): Form
    {
        return $form
        ->columns(6)
        ->schema([
            Hidden::make('cfdi_id')->default(0),
            Select::make('cfdi_prov')->visible(false)
                ->label('Importar CFDI')
                ->searchable()
                ->columnSpanFull()
                ->options(Almacencfdis::select(DB::raw("CONCAT('Factura: ',Serie,Folio,'  -  Emisor: ',Emisor_Nombre,'  -  Fecha: ',DATE_FORMAT(Fecha,'%d-%m-%Y'),'  -  Importe:  $',FORMAT(Total,2)) as recibo,id"))
                ->where('team_id',Filament::getTenant()->id)
                ->where('xml_type','Recibidos')
                ->where('TipoDeComprobante','I')
                ->where('comp_used','NO')
                ->pluck('recibo','id'))
                ->live(onBlur: true)
            ->afterStateUpdated(function(Get $get,Set $set){
                $fact = $get('cfdi_prov');
                $set('cfdi_id',$fact);
                //dd($fact);
                if($fact > 0){
                    $fact = Almacencfdis::where('id',$fact)->first();
                    $prov = 0;
                    if(!Proveedores::where('rfc',$fact->Emisor_Rfc)->where('team_id',Filament::getTenant()->id)->exists()){
                        $cve = count(Proveedores::where('team_id',Filament::getTenant()->id)->get()) + 1;
                        $prov_ = Proveedores::create([
                            'clave'=>$cve,
                            'rfc' => $fact->Emisor_Rfc,
                            'nombre' => $fact->Emisor_Nombre,
                            'team_id' => Filament::getTenant()->id,
                        ]);
                        $prov = $prov_->id;
                    }else{
                        $prov = Proveedores::where('rfc',$fact->Emisor_Rfc)->where('team_id',Filament::getTenant()->id)->first()->id;
                    }
                    $set('prov',$prov);
                    $set('nombre',$fact->Emisor_Nombre);
                    $set('fecha',Carbon::create($fact->Fecha)->format('Y-m-d'));
                    $set('moneda',$fact->Moneda);
                    $set('tcambio',$fact->TipoCambio ?? 1.00);
                    $set('recibe',$fact->Receptor_Nombre);
                    $set('subtotal',$fact->SubTotal);
                    $set('Impuestos',($fact->TotalImpuestosTrasladados-$fact->TotalImpuestosRetenidos));
                    $set('iva',$fact->TotalImpuestosTrasladados);
                    $set('retiva',$fact->TotalImpuestosRetenidos);
                    $set('total',$fact->Total);
                    $xml_content = $fact->content;
                    $cfdi = Cfdi::newFromString($xml_content);
                    $comp = $cfdi->getQuickReader();
                    $emisor = $comp->Emisor;
                    $receptor = $comp->Receptor;
                    $conceptos = $comp->Conceptos;
                    $partidas = $get('partidas');
                    $partidas = [];
                    foreach($conceptos() as $concepto){
                        $imp = $concepto->Impuestos;
                        $trasl = $imp->Traslados;
                        $tras = $trasl->Traslado['Importe'];
                        $reten = $imp->Traslados;
                        $rete = $reten->Retencion['Importe'];
                        $partidas []= [
                            'cant'=>$concepto['Cantidad'],
                            'descripcion'=>$concepto['Descripcion'],
                            'costo'=>floatval($concepto['ValorUnitario']??0),
                            'subtotal'=>floatval($concepto['Importe'] ?? 0),
                            'iva'=>floatval($tras ?? 0),
                            'retiva'=>floatval($rete ?? 0),
                            'retisr'=>0,
                            'ieps'=>0,
                            'total'=>floatval($concepto['Importe']??0)+floatval($tras??0)-floatval($rete??0),
                            'unidad'=>$concepto['ClaveUnidad'],
                            'cvesat'=>$concepto['ClaveProdServ'],
                            'prov'=>$prov,
                            'observa'=>'',
                            'idorden'=>0,
                            'team_id'=>Filament::getTenant()->id,
                            'es_xml'=>'SI',
                        ];
                    }
                    $set('partidas',$partidas);
                }
            }),
            Hidden::make('team_id')->default(Filament::getTenant()->id),
            Split::make([
                FieldSet::make('Recepción')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
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
                            ->columnSpan(3)
                            ->live()
                            ->required()
                            ->options(Proveedores::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
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
                            ->readOnly()
                            ->prefix('$')
                            ->default(1.00)->currencyMask(),
                        TextInput::make('recibe')->columnSpan(2),
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
                                        if($get('es_xml') == 'SI') return;
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
                                                    ->options(Inventario::all()->pluck('descripcion','id'))
                                            ])
                                            ->action(function(Set $set,Get $get,$data){
                                                $item = $data['SelItem'];
                                                $set('item',$item);
                                                if($get('es_xml') == 'SI') return;
                                                $cant = $get('cant');
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
                                Hidden::make('prov'),
                                Hidden::make('idorden'),
                                Hidden::make('team_id')->default(Filament::getTenant()->id),
                                Hidden::make('es_xml')->default('NO'),
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
                        Action::make('ImportarOrden')
                            ->visible(function(Get $get){
                                return $get('prov') > 0 && $get('subtotal') == 0;
                            })
                            ->label('Importar Orden')
                            ->badge()->tooltip('Importar Orden de Compra')
                            ->modalCancelActionLabel('Cancelar')
                            ->modalSubmitActionLabel('Importar')
                            ->icon('fas-cart-arrow-down')
                            ->form([
                                Select::make('ordenSel')
                                    ->label('Orden de Compra')
                                    ->searchable()
                                    ->options(function(Get $get){
                                        $prov = $get('prov');
                                        if(!$prov) return [];
                                        return Ordenes::where('team_id',Filament::getTenant()->id)
                                            ->where('prov',$prov)
                                            ->where('estado','Activa')
                                            ->orderBy('fecha','desc')
                                            ->pluck('folio','id');
                                    })
                            ])
                            ->action(function(Get $get, Set $set, $data){
                                $ordenId = $data['ordenSel'] ?? null;
                                if(!$ordenId){
                                    return;
                                }
                                $partidasDB = OrdenesPartidas::where('ordenes_id',$ordenId)->get();
                                $partidas = [];
                                foreach($partidasDB as $p){
                                    $partidas[] = [
                                        'cant' => $p->cant,
                                        'item' => $p->item,
                                        'descripcion' => $p->descripcion,
                                        'costo' => $p->costo,
                                        'subtotal' => $p->subtotal,
                                        'iva' => $p->iva,
                                        'retiva' => $p->retiva,
                                        'retisr' => $p->retisr,
                                        'ieps' => $p->ieps,
                                        'total' => $p->total,
                                        'prov' => $get('prov'),
                                        'unidad' => $p->unidad,
                                        'cvesat' => $p->cvesat,
                                        'observa' => $p->observa,
                                        'idorden' => $ordenId,
                                        'team_id' => Filament::getTenant()->id,
                                    ];
                                }
                                $set('partidas', $partidas);
                                $set('orden', $ordenId);
                                Self::updateTotals2($get,$set);
                            }),
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
                    ])->grow(false),
            ])->columnSpanFull(),
            Forms\Components\Hidden::make('nombre'),
            Forms\Components\Hidden::make('estado')->default('Activa'),
            Forms\Components\Hidden::make('orden')->default(0)
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
                Tables\Actions\ViewAction::make()
                ->icon('fas-eye')
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
                        Ordenes::where('id',$record->orden_id)->update([
                            'estado'=>'Activa'
                        ]);
                        $partidas = ComprasPartidas::where('compras_id',$record->id)->get();
                        foreach($partidas as $p){
                            $cant = $p->cant;
                            OrdenesPartidas::where('id',$p->orden_partida_id)->increment('pendientes',$cant);
                        }
                    }
                }),
                Tables\Actions\Action::make('Imprimir')->icon('fas-print')
                    ->action(function($record,$livewire){
                        $livewire->idorden = $record->id;
                        $livewire->id_empresa = Filament::getTenant()->id;
                        $livewire->getAction('Imprimir_Doc_E')->visible(true);
                        $livewire->replaceMountedAction('Imprimir_Doc_E');
                        $livewire->getAction('Imprimir_Doc_E')->visible(false);
                    }),
                ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nueva Compra')
                ->label('Agregar')->icon('fas-circle-plus')->badge()
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->modalWidth('7xl')->button()
                ->after(function($record){
                    $partidas = ComprasPartidas::where('compras_id',$record->id)->get();
                    // Procesar movimientos de inventario y actualizar enlace con la orden
                    foreach($partidas as $partida)
                    {
                        $arti = $partida->item;
                        $inve = Inventario::where('id',$arti)->first();
                        if($inve && $inve->servicio == 'NO')
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
                                'tercero'=>$record->prov,
                                'team_id'=>Filament::getTenant()->id,
                            ]);

                            $cost = $partida->costo;
                            $nuevaExist = ($inve->exist ?? 0) + $partida->cant;
                            $avgBase = ($inve->p_costo ?? 0) * ($inve->exist ?? 0);
                            $avgp = $avgBase == 0 ? $cost : (($inve->p_costo + $cost) * (($inve->exist ?? 0) + $nuevaExist)) / (($inve->exist ?? 0) + $nuevaExist);
                            Inventario::where('id',$arti)->update([
                                'exist' => $nuevaExist,
                                'u_costo'=>$cost,
                                'p_costo'=>$avgp
                            ]);
                            if($record->orden > 0){
                                OrdenesPartidas::where(['ordenes_id'=>$record->orden,
                                'item'=>$partida->item])->decrement('pendientes',$partida->cant);
                            }

                        }

                        // Enlazar partida de la orden:
                        if($partida->idorden){
                            $op = OrdenesPartidas::where(['ordenes_id'=>$partida->idorden,'item'=>$partida->item])->first();
                            if($op){
                                // Si la cantidad recibida cubre totalmente lo ordenado, marcar como enlazada
                                if((float)$partida->cant >= (float)$op->cant){
                                    OrdenesPartidas::where('id',$op->id)->update(['idcompra'=>$record->folio]);
                                }
                            }
                        }
                    }

                    // Actualizar estado de la orden: al grabar la compra desde una orden, marcar como 'Comprada'
                    if($record->orden){
                        Ordenes::where('id',$record->orden)->update([
                            'estado'=>'Comprada',
                            'compra'=>$record->folio
                        ]);
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
                                        ->get()->pluck('folio','id')
                                )
                        ]);
                    })
                    ->action(function($data,$livewire){
                        $livewire->requ = $data['sel_requisicion'];
                        $livewire->getAction('Importar Requisición')->visible(true);
                        $livewire->replaceMountedAction('Importar Requisición');
                    }),
                Tables\Actions\Action::make('Importar Ord')
                    ->label('Importar Orden')
                    ->icon('fas-file-import')
                    ->form(function (Forms\ComponentContainer $form) {
                        return $form->schema([
                            Select::make('sel_requisicion')->label('Orden de Compra')
                                ->options(
                                    Ordenes::whereIn('estado',['Activa','Parcial'])
                                        ->get()->pluck('folio','id')
                                )
                        ]);
                    })
                    ->action(function($data,$livewire){
                        $livewire->requ = $data['sel_requisicion'];
                        $livewire->getAction('Importar Orden')->visible(true);
                        $livewire->replaceMountedAction('Importar Orden');
                    }),
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

