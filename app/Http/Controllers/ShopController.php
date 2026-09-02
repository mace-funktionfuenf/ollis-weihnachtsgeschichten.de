<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Contracts\View\View;

class ShopController extends Controller
{
    public function show(Shop $shop): View
    {
        return view('pages.shop', ['shop' => $shop]);
    }
}
