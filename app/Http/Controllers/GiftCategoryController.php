<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GiftCategory;
use Illuminate\Contracts\View\View;

class GiftCategoryController extends Controller
{
    public function index(): View
    {
        $giftCategories = GiftCategory::withCount('products')->get();

        return view('pages.gift-category-index', ['giftCategories' => $giftCategories]);
    }

    public function show(GiftCategory $giftCategory): View
    {
        $giftCategory->loadMissing('products');

        return view('pages.gift-category', ['giftCategory' => $giftCategory]);
    }
}
