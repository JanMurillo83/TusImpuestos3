<?php

namespace App\Livewire;

use App\Models\CuentasCobrar;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class Cuentas_Cobrar_Widget extends BaseWidget
{

    public ?string $cliente;
    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query(
                CuentasCobrar::query()
                    ->where('team_id',Filament::getTenant()->id)
                    ->where('cliente',$this->cliente)
                    ->where('concepto',1)
            )
            ->columns([
                Tables\Columns\TextColumn::make('concepto'),
                Tables\Columns\TextColumn::make('descripcion'),
                Tables\Columns\TextColumn::make('documento'),
                Tables\Columns\TextColumn::make('fecha')
                ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('importe')
                    ->numeric(decimalPlaces: 2,decimalSeparator:'.')->prefix('$'),
                Tables\Columns\TextColumn::make('saldo')
                    ->numeric(decimalPlaces: 2,decimalSeparator:'.')->prefix('$'),
            ])->actions([
                Tables\Actions\Action::make('Pagar')
                /*->form(function ($record,Form $form){
                    return $form
                    ->schema(
                        [
                        TextInput::make('Importe')
                            ->default($record->importe)->numeric()
                            ->currencyMask()->prefix('$'),
                        TextInput::make('Saldo')
                            ->default($record->saldo)->numeric()
                            ->currencyMask()->prefix('$'),
                        TextInput::make('Pago')
                            ->default($record->saldo)->numeric()
                            ->currencyMask()->prefix('$'),
                        Select::make('forma')
                            ->label('Forma de Pago')->required()
                            ->options(DB::table('metodos')->pluck('mostrar', 'clave')),
                    ])->columns(2);
                })
                    ->action(function ($record,$data){
                    CuentasCobrar::where('id',$record->id)->decrement('saldo',$data->Pago);
                    Notification::make()->title('Pago Registrado')->success()->send();
                })*/

            ],Tables\Enums\ActionsPosition::BeforeColumns);
    }
}
