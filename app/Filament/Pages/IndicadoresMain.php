<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ReportesController;
use App\Livewire\Indicadores2Widget;
use App\Livewire\Indicadores3Widget;
use App\Livewire\Indicadores4Widget;
use App\Livewire\IndicadoresWidget;
use App\Models\Auxiliares;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;

class IndicadoresMain extends Page
{
    protected static ?string $navigationIcon = 'fas-chart-simple';
    protected static ?string $title = 'Indicadores del Periodo';
    protected static string $view = 'filament.pages.indicadores-main';
    protected ?string $maxContentWidth = 'full';

}
