<?php

namespace App\Livewire;

use App\Models\CuentasCobrar;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CuentasCobrarWidget extends BaseWidget
{
    public ?int $cliente = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cuentas por cobrar')
            ->query(
                CuentasCobrar::query()
                    ->where('team_id', Filament::getTenant()->id)
                    ->where('concepto',1)
                    ->when($this->cliente, fn ($q) => $q->where('cliente', $this->cliente))
            )
            ->columns([
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('documento')->label('Documento')->searchable(),
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('vencimiento')->label('Vencimiento')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('importe')->label('Importe')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo')->label('Saldo')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->prefix('$')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('agregar')
                    ->label('Agregar')
                    ->modalHeading('Agregar cuenta por cobrar')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['team_id'] = Filament::getTenant()->id;
                        $data['cliente'] = $this->cliente;
                        if (!isset($data['saldo']) || $data['saldo'] === null || $data['saldo'] === '') {
                            $data['saldo'] = $data['importe'] ?? 0;
                        }
                        return $data;
                    })
                    ->successNotificationTitle('Cuenta creada')
                    ->using(function (array $data) {
                        return CuentasCobrar::create($data);
                    })
                    ->form(function (Form $form) {
                        return $form->schema([
                            Hidden::make('cliente')->default($this->cliente),
                            TextInput::make('concepto')->numeric()->default(1)->required(),
                            TextInput::make('descripcion')->maxLength(255),
                            TextInput::make('documento')->maxLength(255),
                            DatePicker::make('fecha')->default(now())->required(),
                            DatePicker::make('vencimiento')->default(now())->required(),
                            TextInput::make('importe')->label('Importe')->numeric()->required()->prefix('$'),
                            TextInput::make('saldo')->label('Saldo')->numeric()->prefix('$'),
                        ])->columns(2);
                    }),
            ],Tables\Actions\HeaderActionsPosition::Bottom)
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar cuenta por cobrar')
                    ->successNotificationTitle('Cuenta actualizada')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['team_id'] = Filament::getTenant()->id;
                        $data['cliente'] = $this->cliente;
                        return $data;
                    })
                    ->form(function (Form $form) {
                        return $form->schema([
                            Hidden::make('cliente')->default($this->cliente),
                            TextInput::make('concepto')->numeric()->required(),
                            TextInput::make('descripcion')->maxLength(255),
                            TextInput::make('documento')->maxLength(255),
                            DatePicker::make('fecha')->required(),
                            DatePicker::make('vencimiento')->required(),
                            TextInput::make('importe')->label('Importe')->numeric()->required()->prefix('$'),
                            TextInput::make('saldo')->label('Saldo')->numeric()->prefix('$'),
                        ])->columns(2);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Eliminar cuenta por cobrar')
                    ->successNotificationTitle('Cuenta eliminada'),
                ])
            ], Tables\Enums\ActionsPosition::BeforeColumns);
    }
}
