<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product->loadMissing(['audiences', 'giftCategories', 'mediaTypes', 'related']);

        return view('pages.product', ['product' => $product]);
    }
}
