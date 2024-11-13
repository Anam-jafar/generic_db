<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pagination extends Component
{
    public $pagination;
    public $prevPageUrl;
    public $nextPageUrl;
    public $perPage;
    public $perPageUrl;

    public function __construct($pagination, $prevPageUrl, $nextPageUrl, $perPage, $perPageUrl)
    {
        $this->pagination = $pagination;
        $this->prevPageUrl = $prevPageUrl;
        $this->nextPageUrl = $nextPageUrl;
        $this->perPage = $perPage;
        $this->perPageUrl = $perPageUrl;
    }

    public function render()
    {
        return view('components.pagination');
    }
}
