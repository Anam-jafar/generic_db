<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class CollectionTemplateExport implements FromArray
{
    protected $templateData;

    public function __construct(array $templateData)
    {
        $this->templateData = $templateData;
    }

    public function array(): array
    {
        return $this->templateData;
    }
}
