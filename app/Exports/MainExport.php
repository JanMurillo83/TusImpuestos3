<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class MainExport implements FromView
{
    use Exportable;
    public string $vista;
    public array $data;
    public function __construct(string $vista, array $data)
    {
        $this->vista = $vista;
        $this->data = $data;
    }
    public function view() :View
    {
        return view($this->vista,$this->data);
    }
}
