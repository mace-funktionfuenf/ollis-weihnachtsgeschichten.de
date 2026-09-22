<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class CategoryController extends Controller
{
    /**
     * These three leaf categories were imported straight from WordPress
     * post-category archives, but what they're meant to showcase - books,
     * DVDs, audio - already exists as real Product rows tagged by MediaType
     * (see MediaTypeController). None of them have their own child
     * categories or posts, so without this they'd render nothing but the
     * "no stories yet" empty state. Map each to the MediaType(s) it stands
     * for so the page can show live product cards instead.
     */
    private const MEDIA_TYPES_BY_CATEGORY_SLUG = [
        'weihnachtsgeschichten-zum-vorlesen' => ['buecher'],
        'weihnachtsgeschichten-auf-dvd' => ['weihnachtsfilme'],
        'weihnachtsgeschichten-als-hoerbuch' => ['hoerspiele', 'hoerbuecher'],
    ];

    public function show(Category $category): View
    {
        $category->loadMissing(['children', 'posts' => fn ($query) => $query->where('status', 'publish')->orderByDesc('published_at')]);

        $products = new Collection;

        if ($mediaTypeSlugs = self::MEDIA_TYPES_BY_CATEGORY_SLUG[$category->slug] ?? null) {
            $products = Product::whereHas('mediaTypes', fn ($query) => $query->whereIn('slug', $mediaTypeSlugs))
                ->where('available', true)
                ->get();
        }

        return view('pages.category', ['category' => $category, 'products' => $products]);
    }
}
