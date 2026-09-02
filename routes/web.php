<?php

declare(strict_types=1);

use App\Http\Controllers\AudienceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GiftCategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaTypeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show']);

Route::get('/produkt/{product:slug}', [ProductController::class, 'show']);
Route::get('/shop/{shop:slug}', [ShopController::class, 'show']);

// Product taxonomy archives - legacy URL shape confirmed against the live
// sitemaps (fuer-sitemap.xml, weihnachtsgeschenke-sitemap.xml,
// weihnachtsgeschichten-sitemap.xml).
Route::get('/fuer/{audience:slug}', [AudienceController::class, 'show']);

Route::get('/weihnachtsgeschenke', [GiftCategoryController::class, 'index']);
Route::get('/weihnachtsgeschenke/{giftCategory:slug}', [GiftCategoryController::class, 'show']);

// "weihnachtsgeschichten" is both a post category (root archive) and a
// product taxonomy (nested media-type archives beneath the same base path)
// - both are real on the live site, per category-sitemap.xml /
// weihnachtsgeschichten-sitemap.xml.
Route::get('/weihnachtsgeschichten', function () {
    return app(CategoryController::class)->show(Category::where('slug', 'weihnachtsgeschichten')->firstOrFail());
});
Route::get('/weihnachtsgeschichten/{mediaType:slug}', [MediaTypeController::class, 'show']);

// The only hierarchical post category on the legacy site.
Route::get('/die-schoensten-weihnachtsgeschichten/{category:slug}', [CategoryController::class, 'show']);

// Pages, flat post categories, and posts all sat at the same bare "/{slug}/"
// depth on the legacy site - one route tries each in turn, per the
// migration skill's guidance for multiple post types sharing a flat depth.
Route::get('/{slug}', function (string $slug) {
    if ($page = Page::where('slug', $slug)->first()) {
        return app(PageController::class)->show($page);
    }

    if ($category = Category::where('slug', $slug)->whereNull('parent_id')->first()) {
        return app(CategoryController::class)->show($category);
    }

    if ($post = Post::where('slug', $slug)->where('status', 'publish')->first()) {
        return app(PostController::class)->show($post);
    }

    abort(404);
})->where('slug', '[A-Za-z0-9\-]+');

Route::fallback(function () {
    $redirect = Redirect::where('from_path', '/'.request()->path())->first();

    if ($redirect) {
        return redirect($redirect->to_path, $redirect->status_code);
    }

    abort(404);
});
