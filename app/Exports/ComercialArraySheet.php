<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

class ComercialArraySheet implements FromArray, WithHeadings, WithTitle, WithStrictNullComparison
{
    /**
     * @var array<int, string>
     */
    private array $headings;

    /**
     * @var array<int, array<int, mixed>>
     */
    private array $rows;

    private string $title;

    /**
     * @param array<int, string> $headings
     * @param array<int, array<int, mixed>> $rows
     */
    public function __construct(string $title, array $headings, array $rows)
    {
        $this->title = $title;
        $this->headings = $headings;
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->title;
    }
}
