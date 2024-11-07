<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CollectionTemplateExport implements FromArray, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_slice($this->data, 1);
    }

    public function headings(): array
    {
        return $this->data[0]; // The first row will be the headers
    }

    public function title(): string
    {
        return 'Collection Data'; // Title for the worksheet
    }
}
