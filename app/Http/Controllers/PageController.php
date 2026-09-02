<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        return view('pages.page', ['page' => $page]);
    }
}
