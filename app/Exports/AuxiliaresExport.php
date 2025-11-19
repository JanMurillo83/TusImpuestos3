<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class AuxiliaresExport implements FromView
{
    use Exportable;
    public function __construct(int $empresa, int $periodo, int $ejercicio, ?string $cuenta_ini = null, ?string $cuenta_fin = null)
    {
        $this->empresa = $empresa;
        $this->periodo = $periodo;
        $this->ejercicio = $ejercicio;
        $this->cuenta_ini = $cuenta_ini;
        $this->cuenta_fin = $cuenta_fin;
    }

    public function view(): View
    {
        return view('AuxiliaresPeriodo_Excel',[
            'empresa'=>$this->empresa,
            'periodo'=>$this->periodo,
            'ejercicio'=>$this->ejercicio,
            'cuenta_ini'=>$this->cuenta_ini,
            'cuenta_fin'=>$this->cuenta_fin,
        ]);
    }
}
