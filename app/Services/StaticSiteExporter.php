<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\AdventCalendarController;
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
use App\Models\GiftCategory;
use App\Models\MediaType;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductAudience;
use App\Models\Shop;
use Illuminate\Support\Facades\File;

/**
 * Writes real, pre-rendered .html files under public/cache/<path>/index.html.
 *
 * Visitors are served these files directly by public/.htaccess (falling back
 * to the normal Laravel route only on a cache miss) - the CMS/DB stays the
 * authoring backend, but the actual HTTP response is a static file.
 *
 * Each export*() method calls the same controller the live route uses
 * (as a plain method call, not an HTTP round-trip) so the cached HTML is
 * guaranteed to match what the dynamic route would have rendered.
 */
class StaticSiteExporter
{
    /** @return list<string> exported URL paths */
    public function exportAll(): array
    {
        $paths = [];

        $paths[] = $this->write('/', app(HomeController::class)->show()->render());

        // "startseite" backs the homepage's intro copy (rendered via
        // HomeController above) rather than being routable on its own.
        // "adventskalender" is skipped for the same reason as the category
        // below: its content is now rendered via AdventCalendarController
        // (which loads and includes this Page itself), not the plain page
        // template - explicit skip so this loop can't silently reclaim the
        // URL with the wrong template depending on write order.
        Page::whereNotIn('slug', ['startseite', 'adventskalender'])->get()->each(function (Page $page) use (&$paths) {
            $paths[] = $this->write($page->url(), app(PageController::class)->show($page)->render());
        });

        Post::where('status', 'publish')->get()->each(function (Post $post) use (&$paths) {
            $paths[] = $this->write($post->url(), app(PostController::class)->show($post)->render());
        });

        Product::all()->each(function (Product $product) use (&$paths) {
            $paths[] = $this->write($product->url(), app(ProductController::class)->show($product)->render());
        });

        Shop::all()->each(function (Shop $shop) use (&$paths) {
            $paths[] = $this->write($shop->url(), app(ShopController::class)->show($shop)->render());
        });

        // Root categories sit at the flat "/{slug}/" depth (see routes/web.php);
        // a category with a parent lives under "/{parent-slug}/{slug}/" instead
        // - exporting every category flatly regardless of parent_id used to
        // create an unroutable duplicate at the flat path for each of those
        // children, since the live flat route explicitly excludes them.
        Category::whereNull('parent_id')->get()->each(function (Category $category) use (&$paths) {
            // "adventskalendergeschichten" is a deliberate exception: its old
            // 3-post category archive was superseded by the interactive
            // Advent calendar on 2026-09-10, then that calendar itself moved
            // to "/adventskalender/" - so this URL is now fully retired
            // (redirects to "/adventskalender/", see
            // ConsolidateAdventCalendarUrl) rather than serving anything of
            // its own. The category row and its post associations are
            // untouched in the DB (the 3 posts stay reachable at their own
            // slugs) - it's only skipped here so this loop can't silently
            // resurrect the old archive at this URL.
            if ($category->slug === 'adventskalendergeschichten') {
                return;
            }

            $paths[] = $this->write('/'.$category->slug.'/', app(CategoryController::class)->show($category)->render());
        });

        Category::whereNotNull('parent_id')->with('parent')->get()->each(function (Category $category) use (&$paths) {
            $paths[] = $this->write('/'.$category->parent->slug.'/'.$category->slug.'/', app(CategoryController::class)->show($category)->render());
        });

        ProductAudience::all()->each(function (ProductAudience $audience) use (&$paths) {
            $paths[] = $this->write($audience->url(), app(AudienceController::class)->show($audience)->render());
        });

        MediaType::all()->each(function (MediaType $mediaType) use (&$paths) {
            $paths[] = $this->write($mediaType->url(), app(MediaTypeController::class)->show($mediaType)->render());
        });

        GiftCategory::all()->each(function (GiftCategory $giftCategory) use (&$paths) {
            $paths[] = $this->write($giftCategory->url(), app(GiftCategoryController::class)->show($giftCategory)->render());
        });
        $paths[] = $this->write('/weihnachtsgeschenke/', app(GiftCategoryController::class)->index()->render());

        $paths[] = $this->write('/adventskalender/', app(AdventCalendarController::class)->index()->render());

        return $paths;
    }

    public function purge(): void
    {
        File::deleteDirectory(public_path('cache'));
    }

    private function write(string $urlPath, string $html): string
    {
        $normalized = trim($urlPath, '/');
        $directory = $normalized === '' ? public_path('cache') : public_path('cache/'.$normalized);

        File::ensureDirectoryExists($directory);
        File::put($directory.'/index.html', $html);

        return $urlPath;
    }
}
