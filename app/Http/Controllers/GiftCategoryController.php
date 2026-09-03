<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GiftCategory;
use Illuminate\Contracts\View\View;

class GiftCategoryController extends Controller
{
    public function index(): View
    {
        $giftCategories = GiftCategory::withCount(['products' => fn ($query) => $query->where('available', true)])->get();

        return view('pages.gift-category-index', ['giftCategories' => $giftCategories]);
    }

    public function show(GiftCategory $giftCategory): View
    {
        $giftCategory->load(['products' => fn ($query) => $query->where('available', true)]);

        return view('pages.gift-category', ['giftCategory' => $giftCategory]);
    }
}
