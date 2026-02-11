<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\ClientesResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\ClientesResource\RelationManagers;
use App\Livewire\CuentasCobrarWidget;
use App\Models\CatCuentas;
use App\Models\Clientes;
use App\Models\Inventario;
use App\Models\Regimenes;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class ClientesResource extends Resource
{
    protected static ?string $model = Clientes::class;
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationIcon = 'fas-users';
    protected static ?string $label = 'Cliente';
    protected static ?string $pluralLabel = 'Clientes';
    protected static ?int $navigationSort = 1;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'ventas']);
    }
    protected static ?string $navigationGroup = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Datos del Cliente')
                            ->icon('fas-user')
                            ->schema([
                                Fieldset::make('Fiscales')
                                    ->extraAttributes(['style'=>'gap:0.3rem'])
                                    ->columnSpanFull()
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('clave')
                                            ->required()
                                            ->readOnly()
                                            ->default(function(){
                                                return count(Clientes::all()) + 1;
                                            }),
                                        Forms\Components\TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('rfc')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(
                                                ignoreRecord: true,
                                                modifyRuleUsing: fn ($rule, $state) =>
                                                    strtoupper($state ?? '') === 'XAXX010101000'
                                                        ? $rule->where('team_id', -1) // Si es XAXX010101000, no validar duplicidad
                                                        : $rule->where('team_id', Filament::getTenant()->id)
                                            )
                                            ->default('XAXX010101000'),
                                        Forms\Components\Select::make('regimen')
                                            ->label('Regimen Fiscal')->required()
                                            ->options(Regimenes::all()->pluck('mostrar','clave')),
                                        Forms\Components\TextInput::make('codigo')
                                            ->label('Codigo Postal')
                                            ->maxLength(255)->required(),
                                        Forms\Components\TextInput::make('dias_credito')
                                            ->label('Dias de Credito')
                                            ->numeric()->default(0)->required(),
                                    ]),
                                Fieldset::make('Datos Generales')
                                    ->schema([
                                        Forms\Components\TextInput::make('telefono')
                                            ->tel()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('correo')
                                            ->label('Correo')
                                            ->email()
                                            ->maxLength(255)->required(),
                                        Forms\Components\TextInput::make('correo2')
                                            ->label('Correo 2')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('contacto')
                                            ->maxLength(255),
                                        Forms\Components\Select::make('cuenta_contable')
                                            ->label('Cuenta Contable')
                                            ->searchable()
                                            ->options(
                                                DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                                    ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                                            ),
                                    ])->columns(3),
                                Fieldset::make('Dirección')
                                    ->extraAttributes(['style'=>'gap:0.3rem'])
                                    ->columnSpanFull()
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('calle')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('no_exterior')
                                            ->label('No. Exterior')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('no_interior')
                                            ->label('No. Interior')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('colonia')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('municipio')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('estado')
                                            ->maxLength(255),
                                    ]),
                                Fieldset::make('Datos de Venta')
                                    ->schema([
                                        Forms\Components\TextInput::make('descuento')
                                            ->required()
                                            ->numeric()
                                            ->default(0.00)
                                            ->suffix('%'),
                                        Forms\Components\Select::make('lista')
                                            ->label('Lista de Precios')
                                            ->options([
                                                1 =>'Precio Publico',
                                                2 =>'Lista de Precios 2',
                                                3 =>'Lista de Precios 3',
                                                4 =>'Lista de Precios 4',
                                                5 =>'Lista de Precios 5'
                                            ])->default(1),
                                        Forms\Components\TextInput::make('saldo')
                                            ->prefix('$')->readOnly()->default(0.00)
                                            ->numeric()->currencyMask(decimalSeparator:'.',precision:2)
                                    ])->columns(3)
                            ])->columns(4),
                        Forms\Components\Tabs\Tab::make('Direcciones de Entrega')
                            ->icon('fas-location-dot')
                            ->schema([
                                Forms\Components\Placeholder::make('direcciones_info')
                                    ->label('')
                                    ->content('Gestiona las direcciones de entrega para este cliente. Estas direcciones se pueden seleccionar al crear cotizaciones.')
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('direccionesEntrega')
                                    ->relationship()
                                    ->label('')
                                    ->addActionLabel('Agregar Dirección')
                                    ->itemLabel(fn (array $state): ?string => $state['nombre_sucursal'] ?? null)
                                    ->collapsed()
                                    ->cloneable()
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\TextInput::make('nombre_sucursal')
                                                    ->label('Nombre de Sucursal')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                            ]),
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('calle')
                                                    ->maxLength(255)
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('no_exterior')
                                                    ->label('No. Exterior')
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('no_interior')
                                                    ->label('No. Interior')
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('colonia')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('municipio')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('estado')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('codigo_postal')
                                                    ->label('Código Postal')
                                                    ->maxLength(10),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('telefono')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('contacto')
                                                    ->maxLength(255),
                                                Forms\Components\Toggle::make('es_principal')
                                                    ->label('Dirección Principal')
                                                    ->default(false),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Precios Especiales')
                            ->icon('fas-tags')
                            ->schema([
                                Forms\Components\Placeholder::make('precios_info')
                                    ->label('')
                                    ->content('Configura precios especiales para productos específicos. Estos precios tienen prioridad sobre los precios por volumen generales.')
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('preciosVolumenClientes')
                                    ->relationship()
                                    ->label('')
                                    ->addActionLabel('Agregar Precio Especial')
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $data['team_id'] = Filament::getTenant()->id;
                                        return $data;
                                    })
                                    ->itemLabel(function (array $state): ?string {
                                        if (!isset($state['producto_id'])) return null;
                                        $producto = \App\Models\Inventario::find($state['producto_id']);
                                        $desde = $state['cantidad_desde'] ?? 0;
                                        $hasta = $state['cantidad_hasta'] ?? '∞';
                                        $precio = $state['precio_unitario'] ?? 0;
                                        return ($producto ? $producto->descripcion : 'Producto') .
                                               " ({$desde}-{$hasta} unidades) → $" . number_format($precio, 2);
                                    })
                                    ->collapsed()
                                    ->cloneable()
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('producto_id')
                                                    ->label('Producto')
                                                    ->searchable()
                                                    ->required()
                                                    ->options(\App\Models\Inventario::where('team_id', Filament::getTenant()->id)
                                                        ->orderBy('descripcion')
                                                        ->pluck('descripcion', 'id'))
                                                    ->columnSpan(2),
                                            ]),
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('cantidad_desde')
                                                    ->label('Cantidad Desde')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1)
                                                    ->minValue(0),
                                                Forms\Components\TextInput::make('cantidad_hasta')
                                                    ->label('Cantidad Hasta')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->helperText('Dejar vacío para sin límite'),
                                                Forms\Components\TextInput::make('precio_unitario')
                                                    ->label('Precio Unitario')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('$')
                                                    ->minValue(0)
                                                    ->currencyMask(decimalSeparator: '.', precision: 6),
                                                Forms\Components\TextInput::make('prioridad')
                                                    ->label('Prioridad')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->helperText('Mayor número = mayor prioridad')
                                                    ->minValue(1),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\DatePicker::make('vigencia_desde')
                                                    ->label('Vigente Desde')
                                                    ->helperText('Dejar vacío para vigencia inmediata'),
                                                Forms\Components\DatePicker::make('vigencia_hasta')
                                                    ->label('Vigente Hasta')
                                                    ->helperText('Dejar vacío para vigencia indefinida'),
                                                Forms\Components\Toggle::make('activo')
                                                    ->label('Activo')
                                                    ->default(true),
                                            ]),
                                    ])
                                    ->defaultItems(0),
                            ]),
                        Forms\Components\Tabs\Tab::make('Equivalencias')
                            ->icon('fas-link')
                            ->schema([
                                Forms\Components\Placeholder::make('equivalencias_info')
                                    ->label('')
                                    ->content('Relaciona la clave interna del inventario con la clave que usa el cliente.')
                                    ->columnSpanFull(),
                                TableRepeater::make('equivalenciasInventario')
                                    ->relationship()
                                    ->label('')
                                    ->addActionLabel('Agregar Equivalencia')
                                    ->headers([
                                        Header::make('Clave Cliente')->width('140px'),
                                        Header::make('Clave Interna')->width('220px'),
                                        Header::make('Descripcion Cliente')->width('260px'),
                                        Header::make('Descripcion Articulo')->width('260px'),
                                        Header::make('Precio')->width('120px'),
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('clave_cliente')
                                            ->label('Clave Cliente')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('clave_articulo')
                                            ->label('Clave Interna')
                                            ->searchable()
                                            ->required()
                                            ->options(
                                                Inventario::where('team_id', Filament::getTenant()->id)
                                                    ->select(DB::raw("CONCAT(clave,' - ',descripcion) as mostrar"), 'clave')
                                                    ->orderBy('clave')
                                                    ->pluck('mostrar', 'clave')
                                            )
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                $clave = trim((string) $state);
                                                if ($clave === '') {
                                                    return;
                                                }

                                                $prod = Inventario::where('team_id', Filament::getTenant()->id)
                                                    ->where('clave', $clave)
                                                    ->first();

                                                if (!$prod) {
                                                    return;
                                                }

                                                $set('descripcion_articulo', $prod->descripcion ?? '');
                                                if (!$get('descripcion_cliente')) {
                                                    $set('descripcion_cliente', $prod->descripcion ?? '');
                                                }
                                            }),
                                        Forms\Components\TextInput::make('descripcion_cliente')
                                            ->label('Descripcion Cliente')
                                            ->required()
                                            ->maxLength(1000),
                                        Forms\Components\TextInput::make('descripcion_articulo')
                                            ->label('Descripcion Articulo')
                                            ->required()
                                            ->maxLength(1000),
                                        Forms\Components\TextInput::make('precio_cliente')
                                            ->label('Precio Cliente')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->currencyMask(decimalSeparator:'.', precision:2),
                                        Hidden::make('team_id')->default(Filament::getTenant()->id),
                                    ])
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('cuenta_contable')
                    ->label('Clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rfc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo')
                    ->numeric(decimalPlaces: 2,decimalSeparator:'.')
                    ->prefix('$')
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar')->icon('fas-edit')
                        ->modalWidth('7xl')
                        ->modalSubmitActionLabel('Grabar')
                        ->modalCancelActionLabel('Cerrar')
                        ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                        ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                        ->modalFooterActionsAlignment(Alignment::Left)
                        ->after(function($record){
                            $record->rfc = strtoupper($record->rfc);
                            $record->save();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->icon('fas-trash')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->hasRole(['administrador'])),
                    Action::make('CxC')->label('Cuentas x Cobrar')
                        ->icon('fas-money-bill-transfer')
                        ->form(function($record){ return [
                            Forms\Components\Livewire::make(CuentasCobrarWidget::class,['cliente'=>$record->id])
                        ];
                        })->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                ])->dropdownPlacement('top-end'),
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                    ->createAnother(false)
                    ->tooltip('Nuevo Cliente')
                    ->label('Agregar')->icon('fas-circle-plus')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->after(function($record){
                        $record->rfc = strtoupper($record->rfc);

                        // Generar cuenta contable si no existe
                        if (empty($record->cuenta_contable)) {
                            $teamId = Filament::getTenant()->id;

                            // Buscar la última cuenta contable de clientes (10501XXX)
                            $ultimaCuenta = CatCuentas::where('team_id', $teamId)
                                ->where('codigo', 'like', '10501%')
                                ->orderBy('codigo', 'desc')
                                ->first();

                            // Generar nuevo código
                            if ($ultimaCuenta) {
                                $ultimoConsecutivo = (int)substr($ultimaCuenta->codigo, 5);
                                $nuevoConsecutivo = str_pad($ultimoConsecutivo + 1, 3, '0', STR_PAD_LEFT);
                            } else {
                                $nuevoConsecutivo = '001';
                            }

                            $nuevoCodigo = '10501' . $nuevoConsecutivo;

                            // Crear la cuenta contable
                            CatCuentas::create([
                                'codigo' => $nuevoCodigo,
                                'nombre' => $record->nombre,
                                'acumula' => '10501',
                                'tipo' => 'D',
                                'naturaleza' => 'D',
                                'csat' => '105.01',
                                'team_id' => $teamId,
                                'rfc_asociado' => $record->rfc
                            ]);

                            // Actualizar el cliente con la nueva cuenta
                            $record->cuenta_contable = $nuevoCodigo;
                        }

                        $record->save();
                    }),
                Action::make('ImpProd')
                    ->label('Importar')
                    ->icon('fas-file-excel')
                    ->modalSubmitActionLabel('Importar')
                    ->modalCancelActionLabel('Cancelar')
                    ->form([
                        FileUpload::make('ExcelFile')
                            ->label('Seleccionar Archivo')
                            ->storeFiles(false)
                    ])->action(function($data){
                        //dd($data['ExcelFile']->path());
                        $archivo = $data['ExcelFile']->path();
                        $tipo=IOFactory::identify($archivo);
                        $lector=IOFactory::createReader($tipo);
                        $libro = $lector->load($archivo, IReader::IGNORE_EMPTY_CELLS);
                        $hoja = $libro->getActiveSheet();
                        $rows = $hoja->toArray();
                        $r = 0;
                        $clientes =Clientes::all();
                        $clave = count($clientes) + 1;
                        $teamId = Filament::getTenant()->id;
                        $seenRfcs = [];
                        foreach($rows as $row)
                        {
                            if($r > 0)
                            {
                                $rfc = strtoupper(trim($row[1] ?? ''));
                                if ($rfc === '' || isset($seenRfcs[$rfc])) {
                                    $r++;
                                    continue;
                                }
                                $seenRfcs[$rfc] = true;
                                if (DB::table('clientes')->where('team_id', $teamId)->where(DB::raw('UPPER(rfc)'), $rfc)->exists()) {
                                    $r++;
                                    continue;
                                }
                                DB::table('clientes')->insert([
                                    'clave'=>$clave,
                                    'nombre'=>$row[0],
                                    'rfc'=>$rfc,
                                    'regimen'=>$row[2],
                                    'codigo'=>$row[3],
                                    'direccion'=>$row[7],
                                    'telefono'=>$row[4],
                                    'correo'=>$row[5],
                                    'descuento'=>$row[8],
                                    'lista'=>$row[9],
                                    'contacto'=>$row[6],
                                    'team_id'=>$teamId
                                ]);
                            }
                            $r++;
                            $clave++;
                        }
                        Notification::make()
                            ->title('Registros Importados')
                            ->success()
                            ->send();
                    })
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
            'index' => Pages\ListClientes::route('/'),
            //'create' => Pages\CreateClientes::route('/create'),
            //'edit' => Pages\EditClientes::route('/{record}/edit'),
        ];
    }
}
