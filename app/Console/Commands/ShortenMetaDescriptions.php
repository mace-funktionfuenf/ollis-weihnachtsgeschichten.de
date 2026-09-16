<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Per the 2026-09-16 client review (point 7): every meta description should
 * be at most 150 characters and carry a call-to-action. Unlike
 * FillMetaDescriptions (which only backfills rows that have none at all),
 * this rewrites every row - including hand-written ones that are already
 * fine - to a uniform, budgeted "content summary + CTA" shape, since that's
 * what was explicitly asked for ("bei allen Seiten"). impressum/datenschutz
 * are excluded on purpose: a marketing CTA doesn't belong on a legal page,
 * and their meta descriptions were deliberately hand-written (see
 * SyncLegalPages) rather than auto-generated.
 *
 * The content budget per type is sized so content + CTA (+ the " …" added
 * only when truncation actually happens) never exceeds 150 characters, then
 * a final Str::limit is applied as a hard safety net regardless.
 */
class ShortenMetaDescriptions extends Command
{
    protected $signature = 'content:shorten-meta-descriptions';

    protected $description = 'Rewrite every meta description to at most 150 characters with a CTA';

    private const MAX_LENGTH = 150;

    public function handle(): int
    {
        $postCount = 0;
        Post::all()->each(function (Post $post) use (&$postCount) {
            $cta = ' Jetzt bei Ollis Weihnachtsgeschichten weiterlesen.';
            $summary = $post->summary(self::MAX_LENGTH - mb_strlen($cta) - 2, ' …', preserveWords: true);
            $post->meta_description = $this->clamp($summary.$cta);
            $post->saveQuietly();
            $postCount++;
        });

        $productCount = 0;
        Product::all()->each(function (Product $product) use (&$productCount) {
            $cta = ' Jetzt bei Amazon ansehen und bestellen.';
            $source = $product->body_html ? strip_tags($product->body_html) : $product->title;
            $summary = Str::limit(str($source)->squish()->toString(), self::MAX_LENGTH - mb_strlen($cta) - 2, ' …', preserveWords: true);
            $product->meta_description = $this->clamp($summary.$cta);
            $product->saveQuietly();
            $productCount++;
        });

        $pageCount = 0;
        Page::whereNotIn('slug', ['impressum', 'datenschutz'])->get()->each(function (Page $page) use (&$pageCount) {
            $cta = ' Jetzt entdecken.';
            $summary = Str::limit(str(strip_tags($page->body_html))->squish()->toString(), self::MAX_LENGTH - mb_strlen($cta) - 2, ' …', preserveWords: true);
            $page->meta_description = $this->clamp($summary.$cta);
            $page->saveQuietly();
            $pageCount++;
        });

        $this->info("{$postCount} posts, {$productCount} products, {$pageCount} pages shortened to ≤".self::MAX_LENGTH.' characters.');

        return self::SUCCESS;
    }

    private function clamp(string $text): string
    {
        return Str::limit($text, self::MAX_LENGTH, '', preserveWords: false);
    }
}
