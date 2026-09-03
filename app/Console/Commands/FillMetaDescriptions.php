<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Auto-generates a baseline meta description with a call-to-action for any
 * Post/Product/Page missing one, derived from existing content rather than
 * invented copy. Intended as a "good enough to ship" default - editorial
 * refinement per page is still worthwhile later, done directly in Filament
 * (meta_description is a plain field on each resource). Safe to re-run:
 * only ever fills rows that are currently null, never overwrites hand-written
 * descriptions.
 */
class FillMetaDescriptions extends Command
{
    protected $signature = 'content:fill-meta-descriptions';

    protected $description = 'Backfill a CTA-bearing meta description for any post/product/page missing one';

    public function handle(): int
    {
        $postCount = 0;
        Post::whereNull('meta_description')->orWhere('meta_description', '')->get()->each(function (Post $post) use (&$postCount) {
            $post->meta_description = Str::limit($post->summary(120), 120, '')
                .' Jetzt bei Ollis Weihnachtsgeschichten weiterlesen.';
            $post->saveQuietly();
            $postCount++;
        });

        $productCount = 0;
        Product::whereNull('meta_description')->orWhere('meta_description', '')->get()->each(function (Product $product) use (&$productCount) {
            $base = $product->body_html
                ? Str::limit(str(strip_tags($product->body_html))->squish()->toString(), 100, '')
                : $product->title;
            $product->meta_description = $base.' Jetzt bei Amazon ansehen und bestellen.';
            $product->saveQuietly();
            $productCount++;
        });

        $pageCount = 0;
        Page::whereNull('meta_description')
            ->orWhere('meta_description', '')
            ->whereNotIn('slug', ['impressum', 'datenschutz'])
            ->get()
            ->each(function (Page $page) use (&$pageCount) {
                $page->meta_description = Str::limit(str(strip_tags($page->body_html))->squish()->toString(), 130, '')
                    .' Jetzt entdecken.';
                $page->saveQuietly();
                $pageCount++;
            });

        $this->info("{$postCount} posts, {$productCount} products, {$pageCount} pages updated.");

        return self::SUCCESS;
    }
}
