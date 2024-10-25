<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BancoCuentasResource\Pages;
use App\Filament\Resources\BancoCuentasResource\RelationManagers;
use App\Models\BancoCuentas;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class BancoCuentasResource extends Resource
{
    protected static ?string $model = BancoCuentas::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $label = 'Cuenta Bancaria';
    protected static ?string $pluralLabel = 'Cuentas Bancarias';

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
                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','10200000')->max('codigo')) + 1;
                    if($nuecta == 1) $nuecta = '10201000';
                    return $nuecta;
                })
                    ->maxLength(255)
                    ->readOnly(),
                Forms\Components\Hidden::make('tax_id')
                    ->default(Filament::getTenant()->taxid),
                Forms\Components\TextInput::make('cuenta')
                    ->maxLength(255),
                Forms\Components\Hidden::make('team_id')
                    ->required()
                    ->default(Filament::getTenant()->id),
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
                Tables\Columns\TextColumn::make('tax_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cuenta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('team_id')
                    ->numeric()
                    ->sortable(),
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
