<?php

namespace App\Filament\Resources\CatCuentasResource\Pages;

use App\Filament\Resources\CatCuentasResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCatCuentas extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = CatCuentasResource::class;
    public ?int $selectedRowId = null;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Alta de Cuenta')
                ->icon('fas-square-plus')
                ->extraAttributes(['class' => 'my-sticky-button']),
        ];
    }

    public function selectRecord(string $id): void
    {
        $this->selectedRowId = $id;
        // Dispatch any events you may need here
    }

    public function updatedSelectedRowId(): void
    {
        $this->render();
    }
}
