<?php

namespace App\Filament\Resources\AlmacencfdisResource\Pages;

use App\Filament\Resources\AlmacencfdisResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\Action;
use App\Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Get;
use CfdiUtils;
use CfdiUtils\Cfdi;
use Filament\Facades\Filament;
use App\Models\Almacencfdis;
use Filament\Tables\Filters\Layout;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class ListAlmacencfdis extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = AlmacencfdisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*Actions\CreateAction::make()
            ->label('Agregar')
            ->icon('fas-plus')
            ->createAnother(false),
            Action::make('Registro')
            ->url(AlmacencfdisResource::getUrl('registro'))*/
        ];
    }
}

