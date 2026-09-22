<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdventDoor;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class AdventCalendarController extends Controller
{
    public function index(): View
    {
        return view('pages.advent-calendar', [
            'doors' => AdventDoor::orderBy('day')->get(),
            'page' => Page::where('slug', 'adventskalender')->first(),
        ]);
    }
}
