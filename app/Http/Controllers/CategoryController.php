<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        $category->loadMissing(['children', 'posts' => fn ($query) => $query->where('status', 'publish')->orderByDesc('published_at')]);

        return view('pages.category', ['category' => $category]);
    }
}
