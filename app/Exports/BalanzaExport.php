<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class BalanzaExport implements FromView
{
    use Exportable;
    public function __construct(int $empresa, int $periodo, int $ejercicio)
    {
        $this->empresa = $empresa;
        $this->periodo = $periodo;
        $this->ejercicio = $ejercicio;
    }

    public function view(): View
    {
        return view('BalanzaNewExport',['empresa'=>$this->empresa,'periodo'=>$this->periodo,'ejercicio'=>$this->ejercicio]);
    }
}
