<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BancoCuentasResource\Pages;
use App\Filament\Resources\BancoCuentasResource\RelationManagers;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatPolizas;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class BancoCuentasResource extends Resource
{
    protected static ?string $model = BancoCuentas::class;
    protected static ?string $navigationGroup = 'Bancos';
    protected static ?string $label = 'Cuenta Bancaria';
    protected static ?string $pluralLabel = 'Cuentas Bancarias';
    protected static ?string $navigationIcon ='fas-building-columns';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('clave')
                    ->searchable()
                    ->options([
                        '002'=> '002- BANAMEX',
                        '006'=> '006- BANCOMEXT',
                        '009'=> '009- BANOBRAS',
                        '012'=> '012- BBVA BANCOMER',
                        '014'=> '014- SANTANDER',
                        '019'=> '019- BANJERCITO',
                        '021'=> '021- HSBC',
                        '030'=> '030- BAJIO',
                        '032'=> '032- IXE',
                        '036'=> '036- INBURSA',
                        '037'=> '037- INTERACCIONES',
                        '042'=> '042- MIFEL',
                        '044'=> '044- SCOTIABANK',
                        '058'=> '058- BANREGIO',
                        '059'=> '059- INVEX',
                        '060'=> '060- BANSI',
                        '062'=> '062- AFIRME',
                        '072'=> '072- BANORTE',
                        '102'=> '102- THE ROYAL BANK',
                        '103'=> '103- AMERICAN EXPRESS',
                        '106'=> '106- BAMSA',
                        '108'=> '108- TOKYO',
                        '110'=> '110- JP MORGAN',
                        '112'=> '112- BMONEX',
                        '113'=> '113- VE POR MAS',
                        '116'=> '116- ING',
                        '124'=> '124- DEUTSCHE',
                        '126'=> '126- CREDIT SUISSE',
                        '127'=> '127- AZTECA',
                        '128'=> '128- AUTOFIN',
                        '129'=> '129- BARCLAYS',
                        '130'=> '130- COMPARTAMOS',
                        '131'=> '131- BANCO FAMSA',
                        '132'=> '132- BMULTIVA',
                        '133'=> '133- ACTINVER',
                        '134'=> '134- WAL-MART',
                        '135'=> '135- NAFIN',
                        '136'=> '136- INTERBANCO',
                        '137'=> '137- BANCOPPEL',
                        '138'=> '138- ABC CAPITAL',
                        '139'=> '139- UBS BANK',
                        '140'=> '140- CONSUBANCO',
                        '141'=> '141- VOLKSWAGEN',
                        '143'=> '143- CIBANCO',
                        '145'=> '145- BBASE',
                        '166'=> '166- BANSEFI',
                        '168'=> '168- HIPOTECARIA',
                        '600'=> '600- MONEXCB',
                        '601'=> '601- GBM',
                        '602'=> '602- MASARI',
                        '605'=> '605- VALUE',
                        '606'=> '606- ESTRUCTURADORES',
                        '607'=> '607- TIBER',
                        '608'=> '608- VECTOR',
                        '610'=> '610- B&B',
                        '614'=> '614- ACCIVAL',
                        '615'=> '615- MERRILL LYNCH',
                        '616'=> '616- FINAMEX',
                        '617'=> '617- VALMEX',
                        '618'=> '618- UNICA',
                        '619'=> '619- MAPFRE',
                        '620'=> '620- PROFUTURO',
                        '621'=> '621- CB ACTINVER',
                        '622'=> '622- OACTIN',
                        '623'=> '623- SKANDIA',
                        '626'=> '626- CBDEUTSCHE',
                        '627'=> '627- ZURICH',
                        '628'=> '628- ZURICHVI',
                        '629'=> '629- SU CASITA',
                        '630'=> '630- CB INTERCAM',
                        '631'=> '631- CI BOLSA',
                        '632'=> '632- BULLTICK CB',
                        '633'=> '633- STERLING',
                        '634'=> '634- FINCOMUN',
                        '636'=> '636- HDI SEGUROS',
                        '637'=> '637- ORDER',
                        '638'=> '638- AKALA',
                        '640'=> '640- CB JPMORGAN',
                        '642'=> '642- REFORMA',
                        '646'=> '646- STP',
                        '647'=> '647- TELECOMM',
                        '648'=> '648- EVERCORE',
                        '649'=> '649- SKANDIA',
                        '651'=> '651- SEGMTY',
                        '652'=> '652- ASEA',
                        '653'=> '653- KUSPIT',
                        '655'=> '655- SOFIEXPRESS',
                        '656'=> '656- UNAGRA',
                        '659'=> '659- OPCIONES EMPRESARIALES DEL NORESTE',
                        '901'=> '901- CLS',
                        '902'=> '902- INDEVAL',
                        '670'=> '670- LIBERTAD',
                        '999'=> '999- NA',

                    ]),
                Forms\Components\TextInput::make('banco')
                    ->maxLength(255)
                    ->label('Descripcion')
                    ->columnSpan(3),
                Forms\Components\TextInput::make('codigo')
                ->default( function(){
                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','10200000')->max('codigo'));
                    $nu_1 = substr($nuecta,0,5);
                    $nu_2 = intval($nu_1) + 1;
                    $nuecta = $nu_2.'000';
                    if($nuecta == 0) $nuecta = '10201000';
                    return $nuecta;
                })
                    ->maxLength(255)
                    ->readOnly(),
                Forms\Components\TextInput::make('inicial')
                    ->label('Saldo Inicial')
                    ->default(0),
                Forms\Components\Hidden::make('tax_id')
                    ->default(Filament::getTenant()->taxid),
                Forms\Components\Hidden::make('cuenta')
                    ->default(''),
                Forms\Components\Hidden::make('team_id')
                    ->required()
                    ->default(Filament::getTenant()->id),
                Forms\Components\Hidden::make('ejercicio')
                    ->required()
                    ->default(Filament::getTenant()->ejercicio),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('banco')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Inicial')
                ->getStateUsing(function(Model $record){
                    $sdos =DB::table('saldosbancos')
                    ->where('cuenta',$record->id)
                    ->where('periodo',Filament::getTenant()->periodo)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
                    ->get();
                    if(isset($sdos[0])){
                        return $sdos[0]->inicial;
                    }
                    else{
                        return 0;
                    }
                })->formatStateUsing(function (?string $state) {
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    return $formatter->formatCurrency($state, 'MXN');
                }),
                Tables\Columns\TextColumn::make('Ingresos')
                ->getStateUsing(function(Model $record){
                    $sdos =DB::table('saldosbancos')
                    ->where('cuenta',$record->id)
                    ->where('periodo',Filament::getTenant()->periodo)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
                    ->get();
                    if(isset($sdos[0])){
                        return $sdos[0]->ingresos;
                    }
                    else{
                        return 0;
                    }
                })->formatStateUsing(function (?string $state) {
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    return $formatter->formatCurrency($state, 'MXN');
                }),
                Tables\Columns\TextColumn::make('Egresos')
                ->getStateUsing(function(Model $record){
                    $sdos =DB::table('saldosbancos')
                    ->where('cuenta',$record->id)
                    ->where('periodo',Filament::getTenant()->periodo)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
                    ->get();
                    if(isset($sdos[0])){
                        return $sdos[0]->egresos;
                    }
                    else{
                        return 0;
                    }
                })->formatStateUsing(function (?string $state) {
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    return $formatter->formatCurrency($state, 'MXN');
                }),
                Tables\Columns\TextColumn::make('Actual')
                ->getStateUsing(function(Model $record){
                    $sdos =DB::table('saldosbancos')
                    ->where('cuenta',$record->id)
                    ->where('periodo',Filament::getTenant()->periodo)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
                    ->get();
                    if(isset($sdos[0])){
                        return $sdos[0]->actual;
                    }
                    else{
                        return 0;
                    }
                })->formatStateUsing(function (?string $state) {
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                    return $formatter->formatCurrency($state, 'MXN');
                }),
                Tables\Columns\TextColumn::make('tax_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cuenta')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('team_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ])->striped()->defaultPaginationPageOption(8)
            ->paginated([8, 'all'])
            ->headerActions([
                Tables\Actions\Action::make('traspaso')
                ->label('Traspaso entre Cuentas')
                ->form([
                    Forms\Components\DatePicker::make('fecha')->label('Fecha')->required(),
                    Forms\Components\Select::make('cuenta_origen')->label('Cuenta Origen')
                        ->options(BancoCuentas::all()->pluck('banco','id'))->required(),
                    Forms\Components\Select::make('cuenta_destino')->label('Cuenta Destino')
                        ->options(BancoCuentas::all()->pluck('banco','id'))->required(),
                    Forms\Components\Select::make('moneda_origen')->label('Moneda Origen')
                    ->options(['USD'=>'USD','MXN'=>'MXN',])->required()->default('MXN'),
                    Forms\Components\Select::make('moneda_destino')->label('Moneda Destino')
                        ->options(['USD'=>'USD','MXN'=>'MXN',])->required()->default('MXN'),
                    Forms\Components\TextInput::make('tipo_cam')->label('Tipo de Cambio')
                        ->required()->numeric()->prefix('$')->default(1),
                    Forms\Components\TextInput::make('importe')->label('Importe')
                        ->required()->numeric()->prefix('$')->default(0),

                ])
                ->action(function ($data){
                    $nopoliza = intval(DB::table('cat_polizas')
                    ->where('team_id',Filament::getTenant()->id)
                    ->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
                    ->max('folio')) + 1;
                    $origen = BancoCuentas::where('id',$data['cuenta_origen'])->first();
                    $destino = BancoCuentas::where('id',$data['cuenta_destino'])->first();
                    $poliza = CatPolizas::create([
                        'tipo'=>'Eg',
                        'folio'=>$nopoliza,
                        'fecha'=>$data['fecha'],
                        'concepto'=>'Traspaso entre cuentas',
                        'cargos'=>$data['importe'],
                        'abonos'=>$data['importe'],
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
                        'referencia'=>'F-',
                        'uuid'=>'',
                        'tiposat'=>'Dr',
                        'team_id'=>Filament::getTenant()->id,
                        'idmovb'=>0
                    ]);
                    $polno = $poliza['id'];
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$destino->codigo,
                        'cuenta'=>$destino->banco,
                        'concepto'=>'Traspaso entre cuentas',
                        'cargo'=>$data['importe'],
                        'abono'=>0,
                        'factura'=>'F-',
                        'nopartida'=>1,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$origen->codigo,
                        'cuenta'=>$origen->banco,
                        'concepto'=>'Traspaso entre cuentas',
                        'cargo'=>0,
                        'abono'=>$data['importe'],
                        'factura'=>'F-',
                        'nopartida'=>2,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                    Notification::make('Completado')
                        ->success()
                        ->title('Proceso Terminado')
                        ->send();
                })
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
            'index' => Pages\ListBancoCuentas::route('/'),
            //'create' => Pages\CreateBancoCuentas::route('/create'),
            //'edit' => Pages\EditBancoCuentas::route('/{record}/edit'),
        ];
    }
}
