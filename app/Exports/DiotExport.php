<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class DiotExport implements FromView, WithTitle
{
    use Exportable;

    private $datos;
    private $periodo;
    private $ejercicio;
    private $empresa;

    public function __construct(array $datos, int $empresa, int $periodo, int $ejercicio)
    {
        $this->datos = $datos;
        $this->empresa = $empresa;
        $this->periodo = $periodo;
        $this->ejercicio = $ejercicio;
    }

    public function view(): View
    {
        return view('DiotExport', [
            'datos' => $this->datos,
            'empresa' => $this->empresa,
            'periodo' => $this->periodo,
            'ejercicio' => $this->ejercicio
        ]);
    }

    public function title(): string
    {
        return 'DIOT ' . str_pad($this->periodo, 2, '0', STR_PAD_LEFT) . $this->ejercicio;
    }
}
