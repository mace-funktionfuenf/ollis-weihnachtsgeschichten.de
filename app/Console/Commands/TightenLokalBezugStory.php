<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MediaType;
use App\Models\Post;
use Illuminate\Console\Command;

/**
 * "/weihnachtsgeschichten-mit-lokalem-bezug/" carries a 27-card product grid
 * baked into body_html at WordPress-import time (see ImportWordPress's
 * [produkte]-shortcode handling) - a frozen snapshot rather than a live
 * query, and it currently shows 14 products that are no longer
 * `available` alongside the 13 that still are, since static HTML can't
 * reflect that. The same 27 products are already tagged with the
 * "lokale-weihnachtsgeschichten" MediaType, and post.blade.php already has
 * a ready-but-unused rendering path for a Post's own `products()` pivot
 * (the "Das könnte Ihnen auch gefallen" section) - nothing in the codebase
 * ever populates that pivot. Strips the static grid out of body_html and
 * syncs the pivot instead, so the existing template path takes over and
 * the availability filter it already applies (`->where('available', true)`)
 * actually means something.
 */
class TightenLokalBezugStory extends Command
{
    protected $signature = 'content:tighten-lokal-bezug-story';

    protected $description = 'Replace the baked product grid on weihnachtsgeschichten-mit-lokalem-bezug with a live product sync';

    public function handle(): int
    {
        $post = Post::where('slug', 'weihnachtsgeschichten-mit-lokalem-bezug')->first();

        if (! $post) {
            $this->warn('weihnachtsgeschichten-mit-lokalem-bezug not found, skipping.');

            return self::SUCCESS;
        }

        $mediaType = MediaType::where('slug', 'lokale-weihnachtsgeschichten')->first();

        if (! $mediaType) {
            $this->warn('MediaType "lokale-weihnachtsgeschichten" not found, skipping.');

            return self::SUCCESS;
        }

        if (str_contains($post->body_html, 'class="card-grid"')) {
            $html = preg_replace('#\s*<ul class="card-grid">.*</ul>\s*#s', '', $post->body_html);
            $html = preg_replace('/^\s*&nbsp;\s*$/m', '', (string) $html);
            $post->body_html = trim((string) $html);
            $post->save();
            $this->info('Stripped the baked product grid from body_html.');
        } else {
            $this->info('body_html has no baked product grid, skipping.');
        }

        $productIds = $mediaType->products()->pluck('products.id');
        $post->products()->sync($productIds);
        $this->info("Synced {$productIds->count()} product(s) from MediaType 'lokale-weihnachtsgeschichten' onto the post.");

        return self::SUCCESS;
    }
}
