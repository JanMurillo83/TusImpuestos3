<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ComercialDashboardExport implements WithMultipleSheets
{
    /**
     * @var array<int, object>
     */
    private array $sheets;

    /**
     * @param array<int, object> $sheets
     */
    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    public function sheets(): array
    {
        return $this->sheets;
    }
}
