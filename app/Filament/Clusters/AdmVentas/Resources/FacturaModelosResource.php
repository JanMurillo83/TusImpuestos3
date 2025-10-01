<?php

namespace App\Filament\Clusters\AdmVentas\Resources;

use App\Filament\Clusters\AdmVentas;
use App\Filament\Clusters\AdmVentas\Resources\FacturaModelosResource\Pages;
use App\Models\Claves;
use App\Models\Clientes;
use App\Models\Esquemasimp;
use App\Models\FacturaModelo;
use App\Models\FacturaModeloPartida;
use App\Models\Facturas;
use App\Models\FacturasPartidas;
use App\Models\Formas;
use App\Models\Inventario;
use App\Models\Metodos;
use App\Models\Notasventa;
use App\Models\NotasventaPartidas;
use App\Models\SeriesFacturas;
use App\Models\Unidades;
use App\Models\Usos;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class FacturaModelosResource extends Resource
{
    protected static ?string $model = FacturaModelo::class;
    protected static ?string $navigationIcon = 'fas-clone';
    protected static ?string $label = 'Factura modelo';
    protected static ?string $pluralLabel = 'Facturas modelo';
    protected static ?string $cluster = AdmVentas::class;
    protected static ?int $navigationSort = 5;
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
                            Forms\Components\TextInput::make('nombre_modelo')->label('Nombre')->columnSpan(2),
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
                                        $set('cliente_nombre',$prov->nombre);
                                    }
                                })->disabledOn('edit'),
                            Forms\Components\Hidden::make('cliente_nombre')->label('Nombre del Cliente'),
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
                                ->columnSpan(3)->default('CONTADO'),
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
                                ->columnSpan(2),
                            TableRepeater::make('partidas')
                                ->relationship()
                                ->disabled(function(Get $get){
                                    if($get('clie') > 0)
                                        return false; else return true;
                                })
                                ->addActionLabel('Agregar')
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
                Forms\Components\Hidden::make('nombre'),
                Forms\Components\Hidden::make('estado')->default('Activa'),
                Forms\Components\Textarea::make('observa')
                    ->columnSpan(2)->label('Observaciones')
                    ->rows(3),
                Select::make('peridiocidad')
                ->options(['manual'=>'Manual','custom'=>'Personalizado','daily'=>'Diaria','weekly'=>'Semanal','monthly'=>'Mensual'])
                ->default('custom')
                ->live(onBlur: true)
                ->afterStateUpdated(function(Get $get, Set $set){
                    $t = $get('periodicidad');
                    switch ($t){
                        case 'daily': $set('proxima_emision',now()->addDays(1)); break;
                        case 'weekly': $set('proxima_emision',now()->addDays(7)); break;
                        case 'monthly': $set('proxima_emision',now()->addDays(30)); break;
                        case 'manual': $set('proxima_emision',null); break;
                        case 'custom': $set('proxima_emision',now()->addDays(30)); break;
                    }
                    switch ($t){
                        case 'daily': $set('cada_dias',1); break;
                        case 'weekly': $set('cada_dias',7); break;
                        case 'monthly': $set('cada_dias',30); break;
                        case 'manual': $set('cada_dias',0); break;
                        case 'custom': $set('cada_dias',30); break;
                    }
                }),
                TextInput::make('cada_dias')->numeric()->default(30)
                ->live(onBlur: true)
                ->afterStateUpdated(function(Get $get, Set $set){
                    $t = floatval($get('cada_dias'));
                    $set('proxima_emision',now()->addDays($t));
                }),
                Forms\Components\Toggle::make('activa')->default(true),
                DatePicker::make('proxima_emision')->default(now()->addDays(30)->format('Y-m-d'))->readOnly(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (FacturaModelo $query) => $query->where('team_id', Filament::getTenant()->id))
            ->columns([
                Tables\Columns\TextColumn::make('nombre_modelo')->searchable()->label('Modelo'),
                Tables\Columns\TextColumn::make('cliente_nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('periodicidad')->badge(),
                Tables\Columns\IconColumn::make('activa')->boolean(),
                Tables\Columns\TextColumn::make('proxima_emision')->date(),
                Tables\Columns\TextColumn::make('total')->money('mxn'),
                Tables\Columns\TextColumn::make('updated_at')->since(),
            ])
            ->actions([
                Action::make('emitir')
                    ->label('Emitir ahora')
                    ->button()
                    ->icon('heroicon-m-paper-airplane')
                    ->action(fn (FacturaModelo $record) => static::emitirDesdePlantilla($record))
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->bulkActions([
                BulkAction::make('emitirSeleccion')
                    ->label('Emitir seleccionadas')
                    ->icon('heroicon-m-paper-airplane')
                    ->action(function (array $records) {
                        $ok = 0; $err = 0;
                        foreach ($records as $record) {
                            try {
                                static::emitirDesdePlantilla($record);
                                $ok++;
                            } catch (\Throwable $e) {
                                $err++;
                            }
                        }
                        Notification::make()
                            ->title('Emisión completada')
                            ->body("Exitosas: {$ok} | Errores: {$err}")
                            ->success()->send();
                    })
            ])
            ->headerActions([
                CreateAction::make()->label('Nueva plantilla')
                    ->label('Agregar')->icon('fas-circle-plus')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->modalWidth('full')
                    ->createAnother(false),
            ],HeaderActionsPosition::Bottom)
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacturaModelos::route('/'),
            //'create' => Pages\CreateFacturaModelo::route('/create'),
            //'edit' => Pages\EditFacturaModelo::route('/{record}/edit'),
        ];
    }

    /**
     * Genera una Factura a partir de una plantilla.
     */
    public static function emitirDesdePlantilla(FacturaModelo $plantilla): void
    {
        // Crear encabezado de factura básica (no timbrada)
        $serie = SeriesFacturas::where('team_id', Filament::getTenant()->id)->where('tipo','F')->first();
        $folio = SeriesFacturas::where('team_id',Filament::getTenant()->id)->where('tipo','F')->first()->folio + 1 ?? count(Facturas::all()) + 1;
        $esquema = Esquemasimp::where('id',$plantilla->esquema)->first();
        $ser = $serie?->serie ?? 'A';
        $fact = new Facturas();
        $fact->team_id = Filament::getTenant()->id;
        $fact->serie = $serie?->serie ?? 'A';
        $fact->folio = $folio;
        $fact->docto = $ser.$folio;
        $fact->fecha = now()->format('Y-m-d');
        $fact->clie = $plantilla->clie;
        $fact->nombre = $plantilla->cliente_nombre;
        $fact->esquema = $plantilla->esquema;
        $fact->subtotal = $plantilla->subtotal;
        $fact->iva = $plantilla->iva;
        $fact->retiva = $plantilla->retiva;
        $fact->retisr = $plantilla->retisr;
        $fact->ieps = $plantilla->ieps;
        $fact->total = $plantilla->total;
        $fact->observa = $plantilla->observa;
        $fact->estado = 'Activa';
        $fact->metodo = $plantilla->metodo;
        $fact->forma = $plantilla->forma;
        $fact->uso = $plantilla->uso;
        $fact->uuid = '';
        $fact->condiciones = $plantilla->condiciones;
        $fact->vendedor = 0;
        $fact->anterior = null;
        $fact->timbrado = 0;
        $fact->xml = '';
        $fact->moneda = $plantilla->moneda;
        $fact->tcambio = $plantilla->tcambio;
        $fact->pendiente_pago = $plantilla->total;
        $fact->error_timbrado = '';
        $fact->save();

        // Guardar partidas
        foreach ($plantilla->partidas as $p) {
            FacturasPartidas::create([
                'facturas_id' => $fact->id,
                'item' => $p->item,
                'descripcion' => $p->descripcion,
                'cant' => $p->cant,
                'precio' => $p->precio,
                'subtotal' => $p->subtotal,
                'iva' => $p->iva,
                'retiva' => $p->retiva,
                'retisr' => $p->retisr,
                'ieps' => $p->ieps,
                'total' => $p->total,
                'unidad' => $p->unidad,
                'cvesat' => $p->cvesat,
                'costo' => $p->costo,
                'clie' => $plantilla->clie,
                'observa' => null,
                'anterior' => null,
                'siguiente' => null,
                'por_imp1' => $esquema->iva,
                'por_imp2' => $esquema->retiva,
                'por_imp3' => $esquema->retisr,
                'por_imp4' => $esquema->ieps,
                'team_id' => Filament::getTenant()->id,
            ]);
        }

        // Actualizar folio de serie
        if ($serie) {
            $serie->folio = $folio;
            $serie->save();
        }

        // Actualizar fechas de programación
        $plantilla->ultima_emision = now()->toDateString();
        $plantilla->proxima_emision = static::siguienteFecha($plantilla);
        $plantilla->save();

        Notification::make()->title('Factura creada')->body('Se generó la factura #'.$fact->serie.'-'.$fact->folio)->success()->send();
    }

    protected static function siguienteFecha(FacturaModelo $p): ?string
    {
        if (!$p->activa) return null;
        $base = $p->proxima_emision ? Carbon::parse($p->proxima_emision) : now();
        return match ($p->periodicidad) {
            'daily' => $base->copy()->addDay()->toDateString(),
            'weekly' => $base->copy()->addWeek()->toDateString(),
            'monthly' => $base->copy()->addMonth()->toDateString(),
            'custom' => $base->copy()->addDays(max(1, (int)$p->cada_dias))->toDateString(),
            default => null,
        };
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
}

