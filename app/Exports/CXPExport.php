<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class CXPExport implements FromView
{
    use Exportable;

    public ?string $cliente;
    public function __construct(string $cliente)
    {
        $this->cliente = $cliente;
    }
    public function view():View
    {
        return view('HeaderProveedorXLS',['cliente'=>$this->cliente]);
    }
}
