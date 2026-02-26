<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\RelationManagers;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\EquivalenciaInventarioCliente;
use App\Models\Inventario;
use App\Models\SeriesFacturas;
use App\Services\FacturaFolioService;
use App\Services\PrecioCalculator;
use App\Services\ImpuestosCalculator;
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
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use phpDocumentor\Reflection\Types\True_;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Browsershot\Browsershot;

class CotizacionesResource extends Resource
{
    protected static ?string $model = Cotizaciones::class;
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationIcon = 'fas-file-invoice';
    protected static ?string $label = 'Cotización';
    protected static ?string $pluralLabel = 'Cotizaciones';
    protected static ?int $navigationSort = 1;
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
                    Fieldset::make('Cotizacion')
                        ->schema([
                            Hidden::make('team_id')->default(Filament::getTenant()->id),
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\Select::make('sel_serie')
                                ->label('Serie')
                                ->live(onBlur: true)
                                ->required()
                                ->disabledOn('edit')
                                ->options(SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                    ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                    ->select(DB::raw("id,CONCAT(serie,'-',COALESCE(descripcion,'Default')) as descripcion"))
                                    ->pluck('descripcion', 'id'))
                                ->default(function () {
                                    return SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                        ->value('id');
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
                                        ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                        ->value('serie') ?? 'C';
                                }),
                            Forms\Components\Hidden::make('folio')
                                ->default(function () {
                                    $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                        ->first();
                                    return ($serieRow->folio ?? 0) + 1;
                                }),
                            Forms\Components\TextInput::make('docto')
                                ->label('Documento')
                                ->required()
                                ->readOnly()
                                ->default(function () {
                                    $serieRow = SeriesFacturas::where('team_id', Filament::getTenant()->id)
                                        ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                        ->first();
                                    $serie = $serieRow->serie ?? 'C';
                                    $folio = ($serieRow->folio ?? 0) + 1;
                                    return $serie . $folio;
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
                                    $partidas = $get('partidas') ?? [];
                                    if (!empty($partidas)) {
                                        $partidas = array_map(function ($partida) use ($get) {
                                            $partida['clie'] = $get('clie');
                                            return $partida;
                                        }, $partidas);
                                        $set('partidas', $partidas);
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
                            Forms\Components\TextInput::make('condiciones_pago')
                                ->columnSpan(2)->label('Condiciones de Pago'),
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
                            Actions::make([
                                ActionsAction::make('ImportarExcel')
                                    ->visible(function(Get $get){
                                        // Solo visible en nuevas cotizaciones (sin id) y con cliente seleccionado
                                        return !$get('id') && $get('clie') > 0;
                                    })
                                    ->label('Importar Partidas')
                                    ->badge()->tooltip('Importar Partidas desde Excel')
                                    ->modalCancelActionLabel('Cancelar')
                                    ->modalSubmitActionLabel('Importar')
                                    ->icon('fas-file-excel')
                                    ->form([
                                        Actions::make([
                                            ActionsAction::make('downloadLayoutPartidas')
                                                ->label('Descargar Layout')
                                                ->icon('fas-download')
                                                ->color(Color::Blue)
                                                ->action(fn() => static::downloadLayoutPartidas()),
                                        ]),
                                        FileUpload::make('ExcelFile')
                                            ->label('Archivo Excel')
                                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                            ->helperText('El archivo debe contener: Cantidad, Clave, Descripción, Precio Unitario, Observaciones')
                                            ->storeFiles(false)
                                            ->required()
                                    ])->action(function(Get $get,Set $set,$data){
                                        try {
                                            $archivo = $data['ExcelFile']->path();
                                            $tipo = IOFactory::identify($archivo);
                                            $lector = IOFactory::createReader($tipo);
                                            $libro = $lector->load($archivo, IReader::IGNORE_EMPTY_CELLS);
                                            $hoja = $libro->getActiveSheet();
                                            $rows = $hoja->toArray();

                                            $partidas = [];
                                            $errores = [];

                                            // Saltar encabezado (fila 0)
                                            for($r = 1; $r < count($rows); $r++) {
                                                $row = $rows[$r];

                                                // Validar que la fila tenga datos
                                                if(empty($row[0]) && empty($row[1])) continue;

                                                $cant = floatval($row[0] ?? 0);
                                                $clave = trim($row[1] ?? '');
                                                $descripcion = trim($row[2] ?? '');
                                                $precioUnitario = floatval($row[3] ?? 0);
                                                $observaciones = trim($row[4] ?? '');

                                                // Buscar producto por clave
                                                $prod = Inventario::where('team_id', Filament::getTenant()->id)
                                                    ->where('clave', $clave)
                                                    ->first();

                                                if(!$prod) {
                                                    $errores[] = "Fila ".($r+1).": Producto con clave '{$clave}' no encontrado";
                                                    continue;
                                                }

                                                if($cant <= 0) {
                                                    $errores[] = "Fila ".($r+1).": Cantidad inválida";
                                                    continue;
                                                }

                                                if($precioUnitario <= 0) {
                                                    $errores[] = "Fila ".($r+1).": Precio unitario inválido";
                                                    continue;
                                                }

                                                // Calcular subtotal e impuestos por esquema del producto
                                                $subt = $precioUnitario * $cant;
                                                $taxes = ImpuestosCalculator::fromEsquema($get('esquema'), $subt);

                                                // Usar descripción del Excel si está disponible, sino la del inventario
                                                $descFinal = !empty($descripcion) ? $descripcion : $prod->descripcion;

                                                $partidas[] = [
                                                    'cant' => $cant,
                                                    'item' => $prod->id,
                                                    'descripcion' => $descFinal,
                                                    'precio' => $precioUnitario,
                                                    'subtotal' => $subt,
                                                    'iva' => $taxes['iva'],
                                                    'retiva' => $taxes['retiva'],
                                                    'retisr' => $taxes['retisr'],
                                                    'ieps' => $taxes['ieps'],
                                                    'total' => $taxes['total'],
                                                    'unidad' => $prod->unidad ?? 'H87',
                                                    'cvesat' => $prod->cvesat ?? '01010101',
                                                    'costo' => $prod->p_costo ?? 0,
                                                    'clie' => $get('clie'),
                                                    'observa' => $observaciones
                                                ];
                                            }

                                            if(!empty($errores)) {
                                                Notification::make()
                                                    ->title('Errores en la importación')
                                                    ->body(implode("\n", $errores))
                                                    ->warning()
                                                    ->duration(10000)
                                                    ->send();
                                            }

                                            if(!empty($partidas)) {
                                                $set('partidas', $partidas);
                                                Self::updateTotals2($get, $set);

                                                Notification::make()
                                                    ->title('Importación exitosa')
                                                    ->body(count($partidas).' partidas importadas')
                                                    ->success()
                                                    ->send();
                                            } else {
                                                Notification::make()
                                                    ->title('No se importaron partidas')
                                                    ->body('Verifique el formato del archivo')
                                                    ->danger()
                                                    ->send();
                                            }

                                        } catch(\Exception $e) {
                                            Notification::make()
                                                ->title('Error al importar')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    }),
                                ActionsAction::make('ExportarPartidas')
                                    ->label('Exportar Partidas')
                                    ->badge()->tooltip('Exportar Partidas a Excel')
                                    ->icon('fas-file-export')
                                    ->visible(function (Get $get) {
                                        $partidas = $get('partidas') ?? [];
                                        return !empty($partidas);
                                    })
                                    ->action(function (Get $get) {
                                        $partidas = $get('partidas') ?? [];
                                        return static::exportPartidas($partidas);
                                    })
                            ])->columnSpanFull(),
                            TableRepeater::make('partidas')
                                ->relationship()
                                ->disabled(function (Get $get){
                                    if($get('clie') == 0 || $get('clie') == null) return true;
                                })
                                ->addActionLabel('Agregar')
                                ->headers([
                                    Header::make('Cantidad')->width('80px'),
                                    Header::make('Clave Cliente')->width('80px'),
                                    Header::make('Item')->width('300px'),
                                    Header::make('Descripcion')->width('300px'),
                                    Header::make('Unitario'),
                                    Header::make('Subtotal'),
                                ])->schema([
                                    TextInput::make('cant')->numeric()->default(1)->label('Cantidad')
                                        ->live(onBlur: true)
                                        ->currencyMask(decimalSeparator:'.',precision:2)
                                        ->afterStateUpdated(function(Get $get, Set $set, $state, $old){
                                            // Solo recalcular si el valor realmente cambió
                                            if ($state == $old) {
                                                return;
                                            }

                                            $cant = floatval($get('cant'));
                                            $cli = $get('../../clie');
                                            $itemId = $get('item');

                                            // Recalcular precio solo si NO estamos editando un registro existente
                                            // O si explícitamente la cantidad cambió de manera significativa
                                            $cotizacionId = $get('../../id');
                                            if ($cant > 0 && $cli && $itemId && !$cotizacionId) {
                                                $precio = PrecioCalculator::calcularPrecio(
                                                    $itemId,
                                                    $cli,
                                                    $cant,
                                                    Filament::getTenant()->id
                                                );
                                                $set('precio', $precio);
                                            }

                                            $cost = floatval($get('precio'));
                                            $subt = $cost * $cant;
                                            $set('subtotal',$subt);
                                            $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                            $set('iva', $taxes['iva']);
                                            $set('retiva', $taxes['retiva']);
                                            $set('retisr', $taxes['retisr']);
                                            $set('ieps', $taxes['ieps']);
                                            $set('total', $taxes['total']);
                                            $set('clie',$get('../../clie'));
                                            Self::updateTotals($get,$set);
                                        }),
                                    TextInput::make('clave_cliente')
                                        ->label('Clave Cliente')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function(Get $get, Set $set, $state){
                                            $claveCliente = trim((string) $state);
                                            if ($claveCliente === '') {
                                                return;
                                            }

                                            $clienteId = $get('../../clie');
                                            if (!$clienteId) {
                                                Notification::make()
                                                    ->title('Selecciona un cliente primero')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $equiv = EquivalenciaInventarioCliente::where('team_id', Filament::getTenant()->id)
                                                ->where('cliente_id', $clienteId)
                                                ->where('clave_cliente', $claveCliente)
                                                ->first();

                                            if (!$equiv) {
                                                Notification::make()
                                                    ->title('Clave cliente sin equivalencia')
                                                    ->body("No existe equivalencia para la clave '{$claveCliente}'.")
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $prod = Inventario::where('team_id', Filament::getTenant()->id)
                                                ->where('clave', $equiv->clave_articulo)
                                                ->first();

                                            if (!$prod) {
                                                Notification::make()
                                                    ->title('Producto interno no encontrado')
                                                    ->body("No existe inventario con clave '{$equiv->clave_articulo}'.")
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $set('item', $prod->id);
                                            $descripcionCliente = trim((string) $equiv->descripcion_cliente);
                                            $descripcionArticulo = trim((string) $equiv->descripcion_articulo);
                                            $set('descripcion', $descripcionCliente !== '' ? $descripcionCliente : ($descripcionArticulo !== '' ? $descripcionArticulo : ($prod->descripcion ?? '')));
                                            $set('unidad', $prod->unidad ?? 'H87');
                                            $set('cvesat', $prod->cvesat ?? '01010101');
                                            $set('costo', $prod->p_costo ?? 0);

                                            $cantidad = floatval($get('cant')) ?: 1;
                                            $precio = floatval($equiv->precio_cliente);
                                            if ($precio <= 0) {
                                                $precio = PrecioCalculator::calcularPrecio(
                                                    $prod->id,
                                                    $clienteId,
                                                    $cantidad,
                                                    Filament::getTenant()->id
                                                );
                                            }

                                            $set('precio', $precio);
                                            $subt = $precio * $cantidad;
                                            $set('subtotal', $subt);
                                            $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                            $set('iva', $taxes['iva']);
                                            $set('retiva', $taxes['retiva']);
                                            $set('retisr', $taxes['retisr']);
                                            $set('ieps', $taxes['ieps']);
                                            $set('total', $taxes['total']);
                                            $set('clie', $clienteId);
                                            Self::updateTotals($get,$set);
                                        }),
                                    Select::make('item')
                                        ->searchable()
                                        ->options(Inventario::where('team_id',Filament::getTenant()->id)
                                            ->select('id',DB::raw('CONCAT("Item: ",descripcion,"  Exist: ",FORMAT(exist,2)) as descripcion'))
                                            ->pluck('descripcion','id'))
                                        ->required()
                                        ->live(onBlur:true)
                                        ->afterStateUpdated(function(Get $get, Set $set){
                                            $cli = $get('../../clie');
                                            $prod = Inventario::where('id',$get('item'))->first();
                                            if(!$prod) return;
                                            $equiv = null;
                                            $claveCliente = trim((string) $get('clave_cliente'));
                                            if ($claveCliente !== '' && $cli) {
                                                $equiv = EquivalenciaInventarioCliente::where('team_id', Filament::getTenant()->id)
                                                    ->where('cliente_id', $cli)
                                                    ->where('clave_cliente', $claveCliente)
                                                    ->first();
                                            }

                                            $usarEquiv = $equiv && $equiv->clave_articulo === $prod->clave;
                                            if ($usarEquiv) {
                                                $descripcionCliente = trim((string) $equiv->descripcion_cliente);
                                                $descripcionArticulo = trim((string) $equiv->descripcion_articulo);
                                                $set('descripcion', $descripcionCliente !== '' ? $descripcionCliente : ($descripcionArticulo !== '' ? $descripcionArticulo : ($prod->descripcion ?? '')));
                                            } else {
                                                $set('descripcion',$prod->descripcion ?? 'No se selecciono producto');
                                            }
                                            $set('unidad',$prod->unidad ?? 'H87');
                                            $set('cvesat',$prod->cvesat ?? '01010101');
                                            $set('costo',$prod->p_costo ?? 0);

                                            // Obtener cantidad actual
                                            $cantidad = floatval($get('cant')) ?: 1;

                                            // Calcular precio usando el nuevo sistema de precios por volumen
                                            if($prod) {
                                                if ($usarEquiv && floatval($equiv->precio_cliente) > 0) {
                                                    $precio = floatval($equiv->precio_cliente);
                                                } else {
                                                    $precio = PrecioCalculator::calcularPrecio(
                                                        $prod->id,
                                                        $cli,
                                                        $cantidad,
                                                        Filament::getTenant()->id
                                                    );
                                                }
                                            }

                                            $set('precio',$precio);
                                            $cant = floatval($get('cant')) ?: 1;
                                            $subt = $precio * $cant;
                                            $set('subtotal',$subt);
                                            $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                            $set('iva', $taxes['iva']);
                                            $set('retiva', $taxes['retiva']);
                                            $set('retisr', $taxes['retisr']);
                                            $set('ieps', $taxes['ieps']);
                                            $set('total', $taxes['total']);
                                        }),
                                    TextInput::make('descripcion'),
                                    TextInput::make('precio')
                                        ->numeric()
                                        ->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function(Get $get, Set $set, $state, $old){
                                            // Solo recalcular si el valor realmente cambió
                                            if ($state == $old) {
                                                return;
                                            }

                                            $cant = floatval($get('cant'));
                                            $cost = floatval($get('precio'));
                                            $subt = $cost * $cant;
                                            $set('subtotal',$subt);
                                            $taxes = ImpuestosCalculator::fromEsquema($get('../../esquema'), $subt);
                                            $set('iva',$taxes['iva']);
                                            $set('retiva',$taxes['retiva']);
                                            $set('retisr',$taxes['retisr']);
                                            $set('ieps',$taxes['ieps']);
                                            $set('total',$taxes['total']);
                                            $set('clie',$get('../../clie'));
                                            Self::updateTotals($get,$set);
                                        }),
                                    TextInput::make('subtotal')
                                        ->numeric()
                                        ->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                                    Hidden::make('iva')->default(0),
                                    Hidden::make('retiva')->default(0),
                                    Hidden::make('retisr')->default(0),
                                    Hidden::make('ieps')->default(0),
                                    Hidden::make('total')->default(0),
                                    Hidden::make('unidad'),
                                    Hidden::make('cvesat'),
                                    Hidden::make('clie'),
                                    Hidden::make('observa'),
                                    Hidden::make('siguiente'),
                                    Hidden::make('costo'),
                                ])->columnSpan('full')->streamlined(),
                            Section::make('Datos de Entrega')
                                ->schema([
                                    Forms\Components\Select::make('direccion_entrega_id')
                                        ->label('Seleccionar Dirección de Entrega')
                                        ->searchable()
                                        ->live()
                                        ->columnSpanFull()
                                        ->options(function (Get $get) {
                                            $clienteId = $get('clie');
                                            if (!$clienteId) {
                                                return [];
                                            }
                                            return \App\Models\DireccionesEntrega::where('cliente_id', $clienteId)
                                                ->get()
                                                ->mapWithKeys(function ($direccion) {
                                                    $label = $direccion->nombre_sucursal;
                                                    if ($direccion->calle) {
                                                        $label .= ' - ' . $direccion->calle . ' ' . $direccion->no_exterior;
                                                    }
                                                    return [$direccion->id => $label];
                                                });
                                        })
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            if (!$state) {
                                                return;
                                            }
                                            $direccion = \App\Models\DireccionesEntrega::find($state);
                                            if ($direccion) {
                                                $set('entrega_lugar', $direccion->nombre_sucursal);

                                                $direccionCompleta = collect([
                                                    $direccion->calle,
                                                    $direccion->no_exterior ? 'No. Ext. ' . $direccion->no_exterior : null,
                                                    $direccion->no_interior ? 'No. Int. ' . $direccion->no_interior : null,
                                                    $direccion->colonia,
                                                    $direccion->municipio,
                                                    $direccion->estado,
                                                    $direccion->codigo_postal ? 'C.P. ' . $direccion->codigo_postal : null,
                                                ])->filter()->implode(', ');

                                                $set('entrega_direccion', $direccionCompleta);
                                                $set('entrega_contacto', $direccion->contacto);
                                                $set('entrega_telefono', $direccion->telefono);
                                            }
                                        })
                                        ->helperText('Selecciona una dirección guardada o llena los campos manualmente'),
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
                                        ->label('Elaboró')->default(Filament::auth()->user()->name),
                                    Forms\Components\TextInput::make('nombre_autorizo')
                                        ->label('Autorizó'),
                                ])->columns(3),
                        ])->grow(true)->columns(5),
                    Section::make('Totales')
                        ->schema([
                            Forms\Components\TextInput::make('subtotal')
                                ->readOnly()
                                ->numeric()->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                            Forms\Components\Hidden::make('Impuestos'),
                            Forms\Components\TextInput::make('iva')
                                ->label('IVA')
                                ->readOnly()
                                ->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                            Forms\Components\TextInput::make('retiva')
                                ->label('Ret IVA')
                                ->readOnly()
                                ->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                            Forms\Components\TextInput::make('retisr')
                                ->label('Ret ISR')
                                ->readOnly()
                                ->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                            Forms\Components\TextInput::make('ieps')
                                ->label('IEPS')
                                ->readOnly()
                                ->numeric()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2),
                            Forms\Components\TextInput::make('total')
                                ->numeric()
                                ->readOnly()->prefix('$')->default(0.00)->currencyMask(decimalSeparator:'.',precision:2)
                                ->suffixActions([
                                    Actions\Action::make('Calcular Total')
                                    ->icon('fas-calculator')
                                    ->iconButton()
                                    ->visible(function ($context){
                                        if ($context == 'edit') return true;
                                        else return false;
                                    })->action(function(Get $get, Set $set){
                                        $partidas = $get('partidas');
                                        $subtotal = 0;
                                        $iva = 0;
                                        $retiva = 0;
                                        $retisr = 0;
                                        $ieps = 0;
                                        $total = 0;
                                        foreach($partidas as $partida){
                                            $cant = floatval($partida['cant'] ?? 0);
                                            $prec = floatval($partida['precio'] ?? 0);
                                            $lineSubtotal = $cant * $prec;
                                            $taxes = ImpuestosCalculator::fromEsquema($get('esquema'), $lineSubtotal);
                                            $subtotal += $lineSubtotal;
                                            $iva += $taxes['iva'];
                                            $retiva += $taxes['retiva'];
                                            $retisr += $taxes['retisr'];
                                            $ieps += $taxes['ieps'];
                                            $total += $taxes['total'];
                                        }
                                        $set('subtotal',$subtotal);
                                        $set('iva',$iva);
                                        $set('retiva',$retiva);
                                        $set('retisr',$retisr);
                                        $set('ieps',$ieps);
                                        $set('Impuestos',$iva-$retiva-$retisr+$ieps);
                                        $set('total',$total);
                                    })
                                ]),
                            Actions::make([
                                ActionsAction::make('Imprimir Cotizacion')
                                    ->badge()->tooltip('Imprimir Cotizacion')
                                    ->icon('fas-print')
                                    ->action(function($record){
                                        $idorden = $record->id;
                                        $id_empresa = Filament::getTenant()->id;
                                        $archivo_pdf = 'COT-'.$record->serie.$record->folio.'-'.$record->nombre_elaboro.'-'.$record->nombre.'.pdf';
                                        $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                        if(File::exists($ruta))File::delete($ruta);
                                        $data = ['idcotiza'=>$idorden,'team_id'=>$id_empresa,'clie_id'=>$record->clie];
                                        $html = View::make('NFTO_Cotizacion',$data)->render();
                                        Browsershot::html($html)->format('Letter')
                                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                            ->noSandbox()
                                            ->scale(0.8)->savePdf($ruta);
                                        return response()->download($ruta);
                                    })
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

    public static function downloadLayoutPartidas()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Cantidad',
            'Clave',
            'Descripcion',
            'Precio Unitario',
            'Observaciones',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'layout_partidas_cotizacion.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public static function exportPartidas(array $partidas)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Cantidad',
            'Clave',
            'Descripcion',
            'Precio Unitario',
            'Observaciones',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $itemIds = collect($partidas)->pluck('item')->filter()->unique()->values();
        $clavesPorItem = $itemIds->isEmpty()
            ? collect()
            : Inventario::whereIn('id', $itemIds)->pluck('clave', 'id');

        $row = 2;
        foreach ($partidas as $partida) {
            $itemId = $partida['item'] ?? null;
            $clave = $itemId ? ($clavesPorItem[$itemId] ?? '') : '';
            $sheet->setCellValueByColumnAndRow(1, $row, $partida['cant'] ?? 0);
            $sheet->setCellValueByColumnAndRow(2, $row, $clave);
            $sheet->setCellValueByColumnAndRow(3, $row, $partida['descripcion'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row, $partida['precio'] ?? 0);
            $sheet->setCellValueByColumnAndRow(5, $row, $partida['observa'] ?? '');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'partidas_cotizacion.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('fecha', 'desc')
                    ->orderBy('folio', 'desc');
            })
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
                Tables\Actions\ActionGroup::make([
                    Action::make('Imprimir')
                        ->icon('fas-print')
                        ->modalCancelActionLabel('Cerrar')
                        ->modalSubmitAction('')
                        ->action(function($record){
                            $idorden = $record->id;
                            $id_empresa = Filament::getTenant()->id;
                            $archivo_pdf = 'COT-'.$record->serie.$record->folio.'-'.$record->nombre_elaboro.'-'.$record->nombre.'.pdf';
                            $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                            if(File::exists($ruta))File::delete($ruta);
                            $data = ['idcotiza'=>$idorden,'team_id'=>$id_empresa,'clie_id'=>$record->clie];
                            $html = View::make('NFTO_Cotizacion',$data)->render();
                            Browsershot::html($html)->format('Letter')
                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                ->noSandbox()
                                ->scale(0.8)->savePdf($ruta);
                            return response()->download($ruta);
                        }),
                    Action::make('Generar Factura')
                        ->label('Facturar Cotización')
                        ->icon('fas-file-invoice')
                        ->color(Color::Green)
                        ->visible(false)
                        ->mountUsing(function (Forms\ComponentContainer $form, Model $record) {
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
                            $cot = $record->fresh();
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
                                    'clie' => $cot->clie,
                                    'nombre' => $cot->nombre,
                                    'esquema' => $cot->esquema,
                                    'subtotal' => 0,
                                    'iva' => 0,
                                    'retiva' => 0,
                                    'retisr' => 0,
                                    'ieps' => 0,
                                    'total' => 0,
                                    'moneda' => $cot->moneda,
                                    'tcambio' => $cot->tcambio ?? 1,
                                    'observa' => 'Generada desde Cotización #'.$cot->folio,
                                    'estado' => 'Activa',
                                    'cotizacion_id' => $cot->id,
                                    'team_id' => $teamId,
                                    'metodo' => $cot->metodo ?? 'PUE',
                                    'forma' => $cot->forma ?? '01',
                                    'uso' => $cot->uso ?? 'G03',
                                    'condiciones' => $cot->condiciones ?? 'CONTADO',
                                ]);

                                $subtotal = 0; $iva = 0; $retiva = 0; $retisr = 0; $ieps = 0; $total = 0;

                                foreach ($partidasSeleccionadas as $pData) {
                                    $parOriginal = \App\Models\CotizacionesPartidas::find($pData['partida_id']);
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
                                        'clie' => $cot->clie,
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
                                    'docto' => 'F'.$factura->folio
                                ]);

                                $pendientesTotales = \App\Models\CotizacionesPartidas::where('cotizaciones_id', $cot->id)->sum('pendientes');
                                $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial';
                                $cot->update(['estado' => $nuevoEstado]);

                                DB::commit();
                                Notification::make()->title('Factura generada exitosamente')->success()->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Notification::make()->title('Error al generar factura: ' . $e->getMessage())->danger()->send();
                            }
                        }),
                Action::make('Cancelar')
                    ->icon('fas-ban')
                    ->tooltip('Cancelar')->label('Cancelar Cotización')
                    ->color(Color::Red)
                    ->requiresConfirmation()
                    ->action(function(Model $record){
                        $est = $record->estado;
                        if($est == 'Activa')
                        {
                            \App\Models\Cotizaciones::where('id',$record->id)->update([
                                'estado'=>'Cancelada'
                            ]);
                            Notification::make()
                                ->title('Cotizacion Cancelada')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('Copiar')
                    ->icon('fas-copy')
                    ->label('Copiar Cotización')
                    ->modalCancelActionLabel('Cancelar')
                    ->modalSubmitActionLabel('Copiar')
                    ->mountUsing(function (Forms\ComponentContainer $form, Model $record) {
                        $teamId = Filament::getTenant()->id;
                        $serie = $record->serie ?? 'C';
                        $serieRow = SeriesFacturas::where('team_id', $teamId)
                            ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                            ->where('serie', $serie)
                            ->first();

                        if ($serieRow) {
                            $nuevoFolio_ = ($serieRow->folio ?? 0) + 1;
                            $nuevoFolio = $serieRow->serie . $nuevoFolio_;
                        } else {
                            $ultimoFolio = Cotizaciones::where('team_id', $teamId)
                                ->where('serie', $serie)
                                ->max('folio');
                            $nuevoFolio_ = ((int) $ultimoFolio) + 1;
                            $nuevoFolio = $serie . $nuevoFolio_;
                        }

                        $form->fill([
                            'nuevo_folio' => $nuevoFolio,
                            'clie' => $record->clie,
                            'direccion_entrega_id' => $record->direccion_entrega_id,
                        ]);
                    })
                    ->form([
                        Forms\Components\Section::make('Información de Copia')
                            ->schema([
                                Forms\Components\Placeholder::make('nuevo_folio')
                                    ->label('Nuevo Folio')
                                    ->content(fn (Get $get) => $get('nuevo_folio'))
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('clie')
                                    ->label('Cliente')
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->options(Clientes::all()->pluck('nombre','id'))
                                    ->afterStateUpdated(function(Set $set){
                                        $set('direccion_entrega_id', null);
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('direccion_entrega_id')
                                    ->label('Lugar de Entrega')
                                    ->searchable()
                                    ->options(function (Get $get) {
                                        $clienteId = $get('clie');
                                        if (!$clienteId) {
                                            return [];
                                        }
                                        return \App\Models\DireccionesEntrega::where('cliente_id', $clienteId)
                                            ->get()
                                            ->mapWithKeys(function ($direccion) {
                                                $label = $direccion->nombre_sucursal;
                                                if ($direccion->calle) {
                                                    $label .= ' - ' . $direccion->calle . ' ' . $direccion->no_exterior;
                                                }
                                                return [$direccion->id => $label];
                                            });
                                    })
                                    ->helperText('Selecciona una dirección de entrega para la nueva cotización')
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function(Model $record, array $data){
                        DB::transaction(function () use ($record, $data) {
                            $teamId = Filament::getTenant()->id;

                            $serie = $record->serie ?? 'C';
                            $serieRow = SeriesFacturas::where('team_id', $teamId)
                                ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                ->where('serie', $serie)
                                ->first();
                            if (! $serieRow) {
                                throw new \Exception('No se encontro una serie de cotizaciones configurada.');
                            }

                            $folioData = SeriesFacturas::obtenerSiguienteFolio($serieRow->id);
                            $serie = $folioData['serie'];
                            $nuevoFolio_ = $folioData['folio'];
                            $nuevoFolio = $folioData['docto'];

                            // Obtener información del nuevo cliente
                            $nuevoCliente = Clientes::find($data['clie']);
                            $nuevaDireccionEntrega = null;
                            $entregaLugar = $record->entrega_lugar;
                            $entregaDireccion = $record->entrega_direccion;
                            $entregaContacto = $record->entrega_contacto;
                            $entregaTelefono = $record->entrega_telefono;

                            if (!empty($data['direccion_entrega_id'])) {
                                $nuevaDireccionEntrega = \App\Models\DireccionesEntrega::find($data['direccion_entrega_id']);
                                if ($nuevaDireccionEntrega) {
                                    $entregaLugar = $nuevaDireccionEntrega->nombre_sucursal;
                                    $entregaDireccion = collect([
                                        $nuevaDireccionEntrega->calle,
                                        $nuevaDireccionEntrega->no_exterior ? 'No. Ext. ' . $nuevaDireccionEntrega->no_exterior : null,
                                        $nuevaDireccionEntrega->no_interior ? 'No. Int. ' . $nuevaDireccionEntrega->no_interior : null,
                                        $nuevaDireccionEntrega->colonia,
                                        $nuevaDireccionEntrega->municipio,
                                        $nuevaDireccionEntrega->estado,
                                        $nuevaDireccionEntrega->codigo_postal ? 'C.P. ' . $nuevaDireccionEntrega->codigo_postal : null,
                                    ])->filter()->implode(', ');
                                    $entregaContacto = $nuevaDireccionEntrega->contacto;
                                    $entregaTelefono = $nuevaDireccionEntrega->telefono;
                                }
                            }

                            // Crear encabezado de cotización copiada
                            $nueva = new Cotizaciones();
                            $nueva->team_id = $teamId;
                            $nueva->serie = $serie;
                            $nueva->folio = $nuevoFolio_;
                            $nueva->docto = $nuevoFolio;
                            $nueva->fecha = Carbon::now();
                            $nueva->clie = $data['clie'];
                            $nueva->direccion_entrega_id = $data['direccion_entrega_id'];
                            $nueva->nombre = $nuevoCliente ? $nuevoCliente->nombre : $record->nombre;
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
                            $nueva->entrega_lugar = $entregaLugar;
                            $nueva->entrega_direccion = $entregaDireccion;
                            $nueva->entrega_horario = $record->entrega_horario;
                            $nueva->entrega_contacto = $entregaContacto;
                            $nueva->entrega_telefono = $entregaTelefono;
                            $nueva->condiciones_pago = $record->condiciones_pago;
                            $nueva->condiciones_entrega = $record->condiciones_entrega;
                            $nueva->oc_referencia_interna = $record->oc_referencia_interna;
                            $nueva->nombre_elaboro = $record->nombre_elaboro;
                            $nueva->nombre_autorizo = $record->nombre_autorizo;
                            $nueva->save();

                            // Duplicar partidas
                            $partidas = CotizacionesPartidas::where('cotizaciones_id', $record->id)->get();
                            foreach ($partidas as $par) {
                                CotizacionesPartidas::create([
                                    'cotizaciones_id' => $nueva->id,
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
                                    'clie' => $data['clie'],
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
                                ->title('Cotización copiada correctamente: ' . $nueva->docto)
                                ->body('Cliente: ' . $nueva->nombre)
                                ->success()
                                ->send();
                        });
                    }),
                Tables\Actions\EditAction::make()
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->modalWidth('full')
                    ->after(function($record,$livewire){
                        $record->refresh();
                        $record->syncClienteNombre();
                        $record->fixPartidasSubtotalFromCantidadPrecio();
                        $record->recalculateTotalsFromPartidas();
                        $idorden = $record->id;
                        $partidas_pen = CotizacionesPartidas::where('cotizaciones_id',$record->id)->get();
                        foreach($partidas_pen as $par){
                            CotizacionesPartidas::where('id',$par->id)->update(['pendientes'=>$par->cant]);
                        }
                        Cotizaciones::where('id',$record->id)->update([
                            'nombre_elaboro'=>Filament::auth()->user()->name,
                        ]);
                        $clien = Clientes::where('id',$record->clie)->first();
                        Cotizaciones::where('id',$record->id)->update([
                            'nombre'=>$clien->nombre,
                        ]);
                        $id_empresa = Filament::getTenant()->id;
                        $archivo_pdf = 'COT-'.$record->serie.$record->folio.'-'.$record->nombre_elaboro.'-'.$record->nombre.'.pdf';
                        $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                        if(File::exists($ruta))File::delete($ruta);
                        $data = ['idcotiza'=>$idorden,'team_id'=>$id_empresa,'clie_id'=>$record->clie];
                        $html = View::make('NFTO_Cotizacion',$data)->render();
                        Browsershot::html($html)->format('Letter')
                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                            ->noSandbox()
                            ->scale(0.8)->savePdf($ruta);
                        return response()->download($ruta);
                    })->iconPosition(IconPosition::After),
            ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->createAnother(false)
                    ->tooltip('Nueva Cotizacion')
                    ->label('Agregar')->icon('fas-circle-plus')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->modalWidth('full')
                    ->mutateFormDataUsing(function (array $data): array {
                        $serieId = intval($data['sel_serie'] ?? 0);
                        if (! $serieId) {
                            throw new \Exception('Debe seleccionar una serie para la cotizacion.');
                        }

                        $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);

                        $data['serie'] = $folioData['serie'];
                        $data['folio'] = $folioData['folio'];
                        $data['docto'] = $folioData['docto'];

                        return $data;
                    })
                    ->after(function($record,$livewire){
                        $record->refresh();
                        $record->syncClienteNombre();
                        $record->fixPartidasSubtotalFromCantidadPrecio();
                        $record->recalculateTotalsFromPartidas();
                        $partidas_pen = CotizacionesPartidas::where('cotizaciones_id',$record->id)->get();
                        foreach($partidas_pen as $par){
                            CotizacionesPartidas::where('id',$par->id)->update(['pendientes'=>$par->cant]);
                        }
                        $idorden = $record->id;
                        $id_empresa = Filament::getTenant()->id;
                        $archivo_pdf = 'COT-'.$record->serie.$record->folio.'-'.$record->nombre_elaboro.'-'.$record->nombre.'.pdf';
                        $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                        if(File::exists($ruta))File::delete($ruta);
                        $data = ['idcotiza'=>$idorden,'team_id'=>$id_empresa,'clie_id'=>$record->clie];
                        $html = View::make('NFTO_Cotizacion',$data)->render();
                        Browsershot::html($html)->format('Letter')
                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                            ->noSandbox()
                            ->scale(0.8)->savePdf($ruta);
                        return response()->download($ruta);
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
