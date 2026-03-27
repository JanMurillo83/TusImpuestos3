<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\RelationManagers;
use App\Models\Clientes;
use App\Models\Claves;
use App\Models\ComercialCanal;
use App\Models\ComercialMotivoGanada;
use App\Models\ComercialMotivoPerdida;
use App\Models\ComercialSegmento;
use App\Models\CondicionesPago;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\EquivalenciaInventarioCliente;
use App\Models\Inventario;
use App\Models\SeriesFacturas;
use App\Models\Unidades;
use App\Services\FacturaFolioService;
use App\Services\PrecioCalculator;
use App\Services\ImpuestosCalculator;
use App\Support\DocumentFilename;
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
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
        return auth()->user()->hasRole(['administrador', 'contador', 'ventas', 'compras_cotizaciones', 'operador_comercial']);
    }
    protected static ?string $navigationGroup = 'Ventas';

    public static function form(Form $form): Form
    {
        $condicionesPagoOptions = function (Get $get): array {
            $options = CondicionesPago::where('team_id', Filament::getTenant()->id)
                ->where('activo', 1)
                ->orderBy('sort')
                ->pluck('nombre', 'nombre')
                ->toArray();

            $current = $get('condiciones_pago');
            if ($current && !isset($options[$current])) {
                $options[$current] = $current;
            }

            return $options;
        };

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
                                ->required(function ($context) {
                                    return $context !== 'edit';
                                })
                                ->dehydrated(function ($context) {
                                    return $context !== 'edit';
                                })
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
                                ->afterStateHydrated(function (Set $set, ?Cotizaciones $record) {
                                    if (! $record || empty($record->serie)) {
                                        return;
                                    }

                                    $tenant = Filament::getTenant();
                                    if (! $tenant) {
                                        return;
                                    }

                                    $serieId = SeriesFacturas::where('team_id', $tenant->id)
                                        ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
                                        ->where('serie', $record->serie)
                                        ->value('id');

                                    if ($serieId) {
                                        $set('sel_serie', $serieId);
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
                                ->default(Esquemasimp::where('team_id',Filament::getTenant()->id)->first()->id)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                    static::recalculatePartidasForEsquema($get, $set, $state);
                                })
                                ->disabled(function (?Cotizaciones $record): bool {
                                    return $record !== null && $record->estado !== 'Activa';
                                }),
                            Forms\Components\Textarea::make('observa')
                                ->columnSpan(3)->label('Observaciones')
                                ->rows(1),
                            Forms\Components\Select::make('condiciones_pago')
                                ->label('Condiciones de Pago')
                                ->searchable()
                                ->options($condicionesPagoOptions)
                                ->columnSpan(2),
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
                                    ->disabled(fn (Get $get) => ! $get('clie'))
                                    ->label('Importar Partidas')
                                    ->badge()->tooltip('Importar Partidas desde Excel')
                                    ->modalCancelActionLabel('Cancelar')
                                    ->modalSubmitActionLabel('Importar')
                                    ->icon('fas-file-excel')
                                    ->form([
                                        Select::make('modo_importacion')
                                            ->label('¿Qué hacer con las partidas actuales?')
                                            ->options([
                                                'reemplazar' => 'Reemplazar (elimina las actuales)',
                                                'agregar' => 'Agregar (conservar actuales)',
                                            ])
                                            ->default('reemplazar')
                                            ->required()
                                            ->helperText('Si ya hay partidas puedes elegir si reemplazar o agregar.'),
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
                                                $importadas = count($partidas);
                                                $modo = $data['modo_importacion'] ?? 'reemplazar';
                                                if ($modo === 'agregar') {
                                                    $actuales = $get('partidas') ?? [];
                                                    $partidas = array_values(array_merge($actuales, $partidas));
                                                }
                                                $total = count($partidas);
                                                $set('partidas', $partidas);
                                                Self::updateTotals2($get, $set);

                                                Notification::make()
                                                    ->title('Importación exitosa')
                                                    ->body($modo === 'agregar'
                                                        ? "{$importadas} partidas importadas (total {$total})."
                                                        : "{$importadas} partidas importadas.")
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
                                    Header::make('Cantidad')->width('70px'),
                                    Header::make('Clave Cliente')->width('80px'),
                                    Header::make('SKU')->width('110px'),
                                    //Header::make('Item')->width('320px'),
                                    Header::make('Descripcion')->width('300px'),
                                    Header::make('Clave SAT')->width('140px'),
                                    Header::make('Unidad SAT')->width('100px'),
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

                                            $prod = static::findInventarioBySku((string) $equiv->clave_articulo);

                                            if (!$prod) {
                                                Notification::make()
                                                    ->title('Producto interno no encontrado')
                                                    ->body("No existe inventario con clave '{$equiv->clave_articulo}'.")
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $itemIdActual = (int) ($get('item') ?? 0);
                                            if ($itemIdActual === (int) $prod->id) {
                                                static::applyInventarioToPartida($get, $set, $prod, $equiv, setItem: false);
                                                return;
                                            }

                                            $set('item', (int) $prod->id);
                                            static::applyInventarioToPartida($get, $set, $prod, $equiv, setItem: false);
                                        }),
                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                            $sku = trim((string) $state);
                                            if ($sku === '') {
                                                return;
                                            }

                                            $prod = static::findInventarioBySku($sku);
                                            if (! $prod) {
                                                Notification::make()
                                                    ->title('SKU no encontrado')
                                                    ->body("No existe inventario con SKU '{$sku}'.")
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $itemIdActual = (int) ($get('item') ?? 0);
                                            if ($itemIdActual === (int) $prod->id) {
                                                $equiv = static::findEquivalenciaByClaveCliente(
                                                    (int) ($get('../../clie') ?? 0),
                                                    (string) ($get('clave_cliente') ?? '')
                                                );
                                                static::applyInventarioToPartida($get, $set, $prod, $equiv, setItem: false);
                                                return;
                                            }

                                            $equiv = static::findEquivalenciaByClaveCliente(
                                                (int) ($get('../../clie') ?? 0),
                                                (string) ($get('clave_cliente') ?? '')
                                            );
                                            $set('item', (int) $prod->id);
                                            static::applyInventarioToPartida($get, $set, $prod, $equiv, setItem: false);
                                        })
                                        ->suffixAction(
                                            \Filament\Forms\Components\Actions\Action::make('buscar_item')
                                                ->label('Buscador')
                                                ->icon('fas-circle-question')
                                                ->form([
                                                    Forms\Components\TextInput::make('item_search')
                                                        ->label('Buscar item')
                                                        ->live(debounce: 400)
                                                        ->helperText('Escribe al menos 2 caracteres para buscar por SKU o descripcion.'),
                                                    Forms\Components\Select::make('item_result')
                                                        ->label('Resultados')
                                                        ->options(fn (Get $get): array => static::searchInventarioOptions($get('item_search') ?? ''))
                                                        ->reactive()
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, $livewire): void {
                                                            if (! $state) {
                                                                return;
                                                            }

                                                            $livewire->callMountedFormComponentAction();
                                                        })
                                                        ->required(),
                                                ])
                                                ->modalCancelActionLabel('Cancelar')
                                                ->modalSubmitAction(false)
                                                ->modalWidth('md')
                                                ->action(function (Get $get, Set $set, array $data): void {
                                                    $itemId = $data['item_result'] ?? null;
                                                    if (! $itemId) {
                                                        return;
                                                    }

                                                    $itemId = (int) $itemId;
                                                    $prod = static::findInventarioById($itemId);
                                                    if (! $prod) {
                                                        return;
                                                    }

                                                    $equiv = static::findEquivalenciaByClaveCliente(
                                                        (int) ($get('../../clie') ?? 0),
                                                        (string) ($get('clave_cliente') ?? '')
                                                    );
                                                    $set('item', (int) $itemId);
                                                    static::applyInventarioToPartida($get, $set, $prod, $equiv, setItem: false);
                                                })
                                        ),
                                    Hidden::make('item_text'),
                                    Hidden::make('item')
                                        ->required()
                                        ->live(onBlur:true)
                                        ->afterStateHydrated(function (Get $get, Set $set, $state): void {
                                            $itemId = (int) ($state ?? 0);
                                            if ($itemId <= 0) {
                                                return;
                                            }

                                            $prod = static::findInventarioById($itemId);
                                            if (! $prod) {
                                                return;
                                            }

                                            $set('sku', (string) $prod->clave);
                                            $set('item_text', static::formatInventarioOptionLabel($prod));
                                        })
                                        ->afterStateUpdated(function(Get $get, Set $set): void {
                                            $itemId = (int) ($get('item') ?? 0);
                                            if ($itemId <= 0) {
                                                $set('sku', null);
                                                $set('item_text', null);
                                                return;
                                            }

                                            $prod = static::findInventarioById($itemId);
                                            if (! $prod) {
                                                return;
                                            }

                                            $equiv = static::findEquivalenciaByClaveCliente(
                                                (int) ($get('../../clie') ?? 0),
                                                (string) ($get('clave_cliente') ?? '')
                                            );

                                            static::applyInventarioToPartida($get, $set, $prod, $equiv, setItem: false);
                                        }),
                                    TextInput::make('descripcion'),
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
                                            \Filament\Forms\Components\Actions\Action::make('Cat_cve_sat_partida')
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
                                        ->label('Unidad SAT')
                                        ->searchable()
                                        ->required()
                                        ->options(Unidades::all()->pluck('mostrar','clave'))
                                        ->default('H87'),
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
                                    Select::make('segmento_id')
                                        ->label('Segmento')
                                        ->searchable()
                                        ->options(fn () => ComercialSegmento::where('team_id', Filament::getTenant()->id)
                                            ->where('activo', 1)
                                            ->orderBy('sort')
                                            ->pluck('nombre', 'id')),
                                    Select::make('canal_id')
                                        ->label('Canal')
                                        ->searchable()
                                        ->options(fn () => ComercialCanal::where('team_id', Filament::getTenant()->id)
                                            ->where('activo', 1)
                                            ->orderBy('sort')
                                            ->pluck('nombre', 'id')),
                                    Select::make('estado_comercial')
                                        ->label('Estatus Comercial')
                                        ->options([
                                            'OPEN' => 'Abierta',
                                            'NEGOTIATION' => 'En negociacion',
                                            'WON' => 'Facturada',
                                            'LOST' => 'Perdida',
                                            'EXPIRED' => 'Expirada',
                                        ])->default('OPEN'),
                                    Select::make('probabilidad')
                                        ->label('Probabilidad')
                                        ->options([
                                            0.20 => 'Baja (20%)',
                                            0.50 => 'Media (50%)',
                                            0.80 => 'Alta (80%)',
                                        ])->default(0.20),
                                    Forms\Components\TextInput::make('descuento_pct')
                                        ->label('Descuento (%)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default(0),
                                    Forms\Components\DatePicker::make('cierre_estimado')
                                        ->label('Fecha estimada de cierre'),
                                    Forms\Components\DatePicker::make('vigencia_hasta')
                                        ->label('Vigencia hasta'),
                                    Select::make('motivo_ganada_id')
                                        ->label('Motivo de ganada')
                                        ->searchable()
                                        ->options(fn () => ComercialMotivoGanada::where('team_id', Filament::getTenant()->id)
                                            ->where('activo', 1)
                                            ->orderBy('sort')
                                            ->pluck('nombre', 'id')),
                                    Select::make('motivo_perdida_id')
                                        ->label('Motivo de perdida')
                                        ->searchable()
                                        ->options(fn () => ComercialMotivoPerdida::where('team_id', Filament::getTenant()->id)
                                            ->where('activo', 1)
                                            ->orderBy('sort')
                                            ->pluck('nombre', 'id')),
                                    Forms\Components\Select::make('condiciones_pago')
                                        ->label('Condiciones de Pago')
                                        ->searchable()
                                        ->options($condicionesPagoOptions),
                                    Forms\Components\TextInput::make('condiciones_entrega')
                                        ->label('Condiciones de Entrega'),
                                    Forms\Components\TextInput::make('oc_referencia_interna')
                                        ->label('Referencia Interna'),
                                    Forms\Components\TextInput::make('nombre_elaboro')
                                        ->label('Elaboro')
                                        ->default(Filament::auth()->user()->name)
                                        ->disabled()
                                        ->dehydrated(),
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
                                        $archivo_pdf = DocumentFilename::build('COTIZACION', $record->docto ?? ($record->serie . $record->folio), $record->nombre, $record->fecha);
                                        $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                                        if(File::exists($ruta))File::delete($ruta);
                                        $data = ['idcotiza'=>$idorden,'team_id'=>$id_empresa,'clie_id'=>$record->clie];
                                        $html = View::make('NFTO_Cotizacion',$data)->render();
                                        Browsershot::html($html)->format('Letter')
                                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                            ->noSandbox()
                                            ->scale(0.85)->savePdf($ruta);
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
        $totals = static::calculateTotalsFromPartidas($get('../../partidas'));
        static::applyTotals($set, '../../', $totals);
    }

    public static function updateTotals2(Get $get, Set $set): void
    {
        $totals = static::calculateTotalsFromPartidas($get('partidas'));
        static::applyTotals($set, '', $totals);
    }

    private static function findInventarioById(int $itemId): ?Inventario
    {
        if ($itemId <= 0) {
            return null;
        }

        $tenant = Filament::getTenant();
        if (! $tenant) {
            return null;
        }

        try {
            return Inventario::query()
                ->where('team_id', $tenant->id)
                ->whereKey($itemId)
                ->select('id', 'clave', 'descripcion', 'exist', 'unidad', 'cvesat', 'p_costo', 'precio1')
                ->first();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private static function findInventarioBySku(string $sku): ?Inventario
    {
        $sku = trim($sku);
        if ($sku === '') {
            return null;
        }

        $tenant = Filament::getTenant();
        if (! $tenant) {
            return null;
        }

        try {
            return Inventario::query()
                ->where('team_id', $tenant->id)
                ->where(function (Builder $query) use ($sku): void {
                    $query->where('clave', $sku)
                        ->orWhereRaw('LOWER(clave) = ?', [mb_strtolower($sku)]);
                })
                ->select('id', 'clave', 'descripcion', 'exist', 'unidad', 'cvesat', 'p_costo', 'precio1')
                ->first();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private static function findEquivalenciaByClaveCliente(?int $clienteId, string $claveCliente): ?EquivalenciaInventarioCliente
    {
        $claveCliente = trim($claveCliente);
        if (! $clienteId || $claveCliente === '') {
            return null;
        }

        $tenant = Filament::getTenant();
        if (! $tenant) {
            return null;
        }

        try {
            return EquivalenciaInventarioCliente::query()
                ->where('team_id', $tenant->id)
                ->where('cliente_id', $clienteId)
                ->where('clave_cliente', $claveCliente)
                ->first();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private static function applyInventarioToPartida(Get $get, Set $set, Inventario $prod, ?EquivalenciaInventarioCliente $equiv = null, bool $setItem = true): void
    {
        if ($setItem) {
            $set('item', (int) $prod->id);
        }

        $clienteId = (int) ($get('../../clie') ?? 0);
        $cantidad = floatval($get('cant')) ?: 1;
        $usarEquiv = $equiv && ((string) $equiv->clave_articulo === (string) $prod->clave);

        $set('sku', (string) ($prod->clave ?? ''));
        $set('item_text', static::formatInventarioOptionLabel($prod));

        if ($usarEquiv) {
            $descripcionCliente = trim((string) $equiv->descripcion_cliente);
            $descripcionArticulo = trim((string) $equiv->descripcion_articulo);
            $set('descripcion', $descripcionCliente !== '' ? $descripcionCliente : ($descripcionArticulo !== '' ? $descripcionArticulo : ($prod->descripcion ?? '')));
        } else {
            $set('descripcion', $prod->descripcion ?? 'No se selecciono producto');
        }

        $set('unidad', $prod->unidad ?? 'H87');
        $set('cvesat', $prod->cvesat ?? '01010101');
        $set('costo', $prod->p_costo ?? 0);

        if ($usarEquiv && floatval($equiv->precio_cliente) > 0) {
            $precio = floatval($equiv->precio_cliente);
        } elseif ($clienteId > 0 && Filament::getTenant()) {
            $precio = PrecioCalculator::calcularPrecio(
                $prod->id,
                $clienteId,
                $cantidad,
                Filament::getTenant()->id
            );
        } else {
            $precio = floatval($prod->precio1 ?? 0);
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
        $set('clie', $clienteId > 0 ? $clienteId : null);
        static::updateTotals($get, $set);
    }

    private static function searchInventarioOptions(string $search): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [];
        }

        $search = trim($search);
        if (mb_strlen($search) < 2) {
            return [];
        }

        try {
            $query = Inventario::query()
                ->where('team_id', $tenant->id)
                ->select('id', 'clave', 'descripcion', 'exist');

            $query->where(function (Builder $q) use ($search): void {
                $q->where('clave', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });

            return $query
                ->orderBy('clave')
                ->limit(50)
                ->get()
                ->mapWithKeys(function (Inventario $item): array {
                    return [$item->id => static::formatInventarioOptionLabel($item)];
                })
                ->toArray();
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
    }

    private static function formatInventarioOptionLabel(Inventario $item): string
    {
        return sprintf(
            'SKU: %s  |Desc: %s  |Exist: %s',
            (string) $item->clave,
            (string) $item->descripcion,
            number_format((float) $item->exist, 2, '.', ',')
        );
    }

    private static function calculateTotalsFromPartidas(?array $partidas): array
    {
        $totals = [
            'subtotal' => 0.0,
            'iva' => 0.0,
            'retiva' => 0.0,
            'retisr' => 0.0,
            'ieps' => 0.0,
            'total' => 0.0,
        ];

        foreach (($partidas ?? []) as $partida) {
            if (! is_array($partida)) {
                continue;
            }

            $totals['subtotal'] += (float) ($partida['subtotal'] ?? 0);
            $totals['iva'] += (float) ($partida['iva'] ?? 0);
            $totals['retiva'] += (float) ($partida['retiva'] ?? 0);
            $totals['retisr'] += (float) ($partida['retisr'] ?? 0);
            $totals['ieps'] += (float) ($partida['ieps'] ?? 0);
            $totals['total'] += (float) ($partida['total'] ?? 0);
        }

        return $totals;
    }

    private static function applyTotals(Set $set, string $prefix, array $totals): void
    {
        $set($prefix . 'subtotal', $totals['subtotal']);
        $set($prefix . 'iva', $totals['iva']);
        $set($prefix . 'retiva', $totals['retiva']);
        $set($prefix . 'retisr', $totals['retisr']);
        $set($prefix . 'ieps', $totals['ieps']);
        $set($prefix . 'Impuestos', ($totals['iva'] + $totals['ieps']) - ($totals['retiva'] + $totals['retisr']));
        $set($prefix . 'total', $totals['total']);
    }

    private static function recalculatePartidasForEsquema(Get $get, Set $set, $esquemaId): void
    {
        $partidas = $get('partidas') ?? [];

        if (! is_array($partidas) || empty($partidas) || ! $esquemaId) {
            return;
        }

        $partidasActualizadas = array_map(function ($partida) use ($esquemaId) {
            if (! is_array($partida)) {
                return $partida;
            }

            $cantidad = (float) ($partida['cant'] ?? 0);
            $precio = (float) ($partida['precio'] ?? 0);
            $subtotal = $cantidad * $precio;
            $taxes = ImpuestosCalculator::fromEsquema($esquemaId, $subtotal);

            $partida['subtotal'] = $subtotal;
            $partida['iva'] = $taxes['iva'];
            $partida['retiva'] = $taxes['retiva'];
            $partida['retisr'] = $taxes['retisr'];
            $partida['ieps'] = $taxes['ieps'];
            $partida['total'] = $taxes['total'];

            return $partida;
        }, $partidas);

        $set('partidas', $partidasActualizadas);
        static::applyTotals($set, '', static::calculateTotalsFromPartidas($partidasActualizadas));
    }

    public static function downloadLayoutPartidas()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Cantidad',
            'Clave (SKU)',
            'Descripcion',
            'Precio Unitario',
            'Observaciones',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $sheet->setTitle('Layout');

        $exampleSheet = $spreadsheet->createSheet();
        $exampleSheet->setTitle('Ejemplo');
        foreach ($headers as $index => $header) {
            $exampleSheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }
        $exampleSheet->setCellValueByColumnAndRow(1, 2, 1);
        $exampleSheet->setCellValueByColumnAndRow(2, 2, 'CLAVE-001');
        $exampleSheet->setCellValueByColumnAndRow(3, 2, 'Descripcion ejemplo');
        $exampleSheet->setCellValueByColumnAndRow(4, 2, 123.4567);
        $exampleSheet->setCellValueByColumnAndRow(5, 2, 'Observaciones');
        $exampleSheet->setCellValueByColumnAndRow(1, 4, 'Usa la clave/SKU del item, no el ID.');
        $exampleSheet->mergeCells('A4:E4');

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
            'Clave (SKU)',
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
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->modifyQueryUsing(fn (Builder $query) => $query->with('createdBy'))
            ->columns([
                Tables\Columns\TextColumn::make('docto')
                    ->label('Cotizacion')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->where('cotizaciones.fecha', 'like', "%{$search}%")
                                ->orWhereRaw("DATE_FORMAT(cotizaciones.fecha, '%d-%m-%Y') like ?", ["%{$search}%"]);
                        });
                    }),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('vendedor_elaboro')
                    ->label('Vendedor')
                    ->getStateUsing(fn ($record) => $record->createdBy?->name
                        ?? $record->nombre_elaboro
                        ?? $record->vendedor)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q
                                ->whereHas('createdBy', function (Builder $uq) use ($search): void {
                                    $uq->where('name', 'like', "%{$search}%");
                                })
                                ->orWhere('cotizaciones.nombre_elaboro', 'like', "%{$search}%")
                                ->orWhere('cotizaciones.vendedor', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('cotizaciones.subtotal', 'like', "%{$search}%"))
                    ->currency('USD',true),
                Tables\Columns\TextColumn::make('iva')
                    ->numeric()
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('cotizaciones.iva', 'like', "%{$search}%"))
                    ->currency('USD',true),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('cotizaciones.total', 'like', "%{$search}%"))
                    ->currency('USD',true),
                Tables\Columns\TextColumn::make('moneda')
                    ->label('Moneda')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tcambio')
                    ->label('T.Cambio')
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format((float)$state, 6))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('cotizaciones.tcambio', 'like', "%{$search}%"))
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
                            $archivo_pdf = DocumentFilename::build('COTIZACION', $record->docto ?? ($record->serie . $record->folio), $record->nombre, $record->fecha);
                            $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                            if(File::exists($ruta))File::delete($ruta);
                            $data = ['idcotiza'=>$idorden,'team_id'=>$id_empresa,'clie_id'=>$record->clie];
                            $html = View::make('NFTO_Cotizacion',$data)->render();
                            Browsershot::html($html)->format('Letter')
                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                ->noSandbox()
                                ->scale(0.85)->savePdf($ruta);
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
                                    'created_by_user_id' => Filament::auth()->id(),
                                    'segmento_id' => $cot->segmento_id,
                                    'canal_id' => $cot->canal_id,
                                    'motivo_ganada_id' => $cot->motivo_ganada_id,
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
                                $factura->pendiente_pago = $total;
                                $factura->save();
                                $factura->recalculateCommercialMetrics();

                                $pendientesTotales = \App\Models\CotizacionesPartidas::where('cotizaciones_id', $cot->id)->sum('pendientes');
                                $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial';
                                $cot->update(['estado' => $nuevoEstado, 'estado_comercial' => 'WON']);

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
                            $nueva->created_by_user_id = Filament::auth()->id();
                            $nueva->nombre_elaboro = Filament::auth()->user()->name;
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
                Action::make('Editar')
                    ->label('Editar')
                    ->icon('fas-edit')
                    ->url(fn (Model $record) => static::getUrl('edit', ['record' => $record]))
                    ->iconPosition(IconPosition::After),
            ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('Agregar')
                    ->label('Agregar')
                    ->icon('fas-circle-plus')
                    ->tooltip('Nueva Cotizacion')
                    ->url(static::getUrl('create'))
                    ->button()
                    ->visible(static::canCreate())
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
            'create' => Pages\CreateCotizaciones::route('/create'),
            'edit' => Pages\EditCotizaciones::route('/{record}/edit'),
        ];
    }
}
