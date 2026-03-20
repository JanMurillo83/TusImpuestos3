<?php

namespace App\Filament\Clusters\Herramientas\Resources;

use App\Filament\Clusters\Herramientas;
use App\Filament\Clusters\Herramientas\Resources\AltaUsuariosResource\Pages;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AltaUsuariosResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'fas-users';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $navigationLabel = 'Alta de Usuarios';
    protected static ?string $label = 'Usuario';
    protected static ?string $pluralLabel = 'Alta de Usuarios';
    protected static ?int $navigationSort = 20;
    protected static bool $isScopedToTenant = false;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (strtoupper((string) $user->is_admin) === 'SI') {
            return true;
        }

        if (($user->role ?? null) && in_array($user->role, ['administrador', 'admin'], true)) {
            return true;
        }

        return method_exists($user, 'hasRole')
            ? $user->hasRole(['administrador', 'admin'])
            : false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit'),
                Forms\Components\Hidden::make('email_verified_at')
                    ->default(Carbon::now()),
                Forms\Components\Hidden::make('password')
                    ->default('Admin123'),
                Forms\Components\Hidden::make('team_id')
                    ->default(fn () => Filament::getTenant()?->id),
                Forms\Components\Hidden::make('is_admin')
                    ->default('NO'),
                Forms\Components\Select::make('role')
                    ->label('Rol')
                    ->options(Role::query()->pluck('description', 'name'))
                    ->required()
                    ->default(function ($record) {
                        if ($record && $record->roles->isNotEmpty()) {
                            return $record->roles->first()->name;
                        }

                        return null;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $tenantId = Filament::getTenant()?->id;

                if (! $tenantId) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereHas('teams', function (Builder $subQuery) use ($tenantId) {
                    $subQuery->where('teams.id', $tenantId);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol'),
                Tables\Columns\TextColumn::make('teams_count')
                    ->label('Empresas')
                    ->counts('teams')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('Agregar')
                    ->icon('fas-plus')
                    ->createAnother(false)
                    ->after(function (User $record) {
                        $teamIds = Team::query()->pluck('id');

                        if ($teamIds->isNotEmpty()) {
                            $now = now();
                            $teamUserRows = $teamIds
                                ->map(fn (int $teamId): array => [
                                    'user_id' => $record->getKey(),
                                    'team_id' => $teamId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ])
                                ->all();

                            DB::table('team_user')->insert($teamUserRows);
                        }

                        if ($record->role) {
                            $record->roles()->detach();
                            $record->assignRole($record->role);
                        }

                        Notification::make()
                            ->title('Usuario creado correctamente')
                            ->body('El usuario fue asignado a ' . $teamIds->count() . ' empresa(s).')
                            ->success()
                            ->send();
                    }),
            ], Tables\Actions\HeaderActionsPosition::Bottom)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->after(function (User $record) {
                            if ($record->role) {
                                $record->roles()->detach();
                                $record->assignRole($record->role);
                            }
                        }),
                    Tables\Actions\Action::make('Cambiar Contrasena')
                        ->icon('heroicon-o-key')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->password()
                                ->label('Nueva Contraseña')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update([
                                'password' => $data['new_password'],
                            ]);

                            Notification::make()
                                ->title('Contraseña actualizada correctamente')
                                ->success()
                                ->send();
                        }),
                ]),
            ], Tables\Enums\ActionsPosition::BeforeCells);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAltaUsuarios::route('/'),
        ];
    }
}
