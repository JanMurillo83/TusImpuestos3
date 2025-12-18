<?php

namespace App\Filament\Pages;

use App\Exports\CXCExport;
use App\Exports\CXPExport;
use App\Models\EstadCXC_F_F;
use App\Models\EstadCXP_F_F;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Url;
use Spatie\Browsershot\Browsershot;

class Estadisticacxp_cliente extends Page implements HasTable, HasActions
{
    use InteractsWithTable, InteractsWithActions;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.estadisticacxp_cliente';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Cuentas por Pagar';
    public function getTitle(): string
    {
        return '';
    }
    #[Url]
    public ?string $cliente = null;
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return EstadCXP_F_F::getCliente($this->cliente)->query();
            })
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('fecha','asc'))
            ->header(view('HeaderProveedor',['cliente'=>$this->cliente ?? '']))
            ->paginated(false)
            ->striped()
            ->recordClasses('row_gral')
            ->columns([
                TextColumn::make('factura'),
                TextColumn::make('fecha')->date('d-m-Y'),
                TextColumn::make('vencimiento')->date('d-m-Y'),
                TextColumn::make('importe')->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight(),
                TextColumn::make('pagos')->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight(),
                TextColumn::make('saldo')->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')->alignRight()
                    ->summarize(Sum::make()->numeric(decimalSeparator: '.',thousandsSeparator: ',',decimalPlaces: 2)->prefix('$')),
            ]);
    }
    public function pdfAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('pdf')
            ->action(function () {
                $archivo_pdf = 'ReporteCXC'.Filament::getTenant()->id.'.pdf';
                $ruta = public_path().'/TMPCFDI/'.$archivo_pdf;
                if(File::exists($ruta))File::delete($ruta);
                $data = ['cliente'=>$this->cliente];
                $html = View::make('HeaderProveedorPDF',$data)->render();
                Browsershot::html($html)->format('Letter')
                    ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                    ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                    ->noSandbox()
                    ->scale(0.8)->savePdf($ruta);
                return response()->download($ruta);
            });
    }
    public function xlsAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('xls')
            ->action(function () {
                return (new CXPExport($this->cliente))->download('Estado_de_Cuenta.xlsx');
            });
    }

    public function mailAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('mail')
            ->action(function () {
                dd('Test action called', $this->cliente);
            });
    }
}
