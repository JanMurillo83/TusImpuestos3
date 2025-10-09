<?php

namespace App\Filament\Pages\Paginas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\DB;

class CamTent extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.paginas.cam-tent';
    protected static bool $shouldRegisterNavigation = false;
    public $defaultAction = 'onboarding';
    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->modalHeading('Seleccion de Empresa')
            ->form([
                Select::make('empresa')
                ->options(DB::table('teams')->pluck('name','id')),
                TextInput::make('periodo')
                    ->numeric()->minValue(1)->maxValue(12)
                    ->default(Filament::getTenant()->periodo),
                TextInput::make('ejercicio')
                    ->numeric()
                    ->default(Filament::getTenant()->ejercicio),

            ])->modalWidth(MaxWidth::ExtraSmall)
            ->modalCancelAction(false)
            ->action(function($livewire,$data){
                $livewire->cambia_per($data);
            });
    }
    public function cambia_per($data)
    {
        DB::table('teams')->where('id',$data['empresa'])->update([
            'periodo'=>$data['periodo'],
            'ejercicio'=>$data['ejercicio'],
        ]);
        Notification::make()
            ->title(' Periodo de Trabajo Actualizado')
            ->success()
            ->send();
        redirect('/'.$data['empresa']);
    }
}
