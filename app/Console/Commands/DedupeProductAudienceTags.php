<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GiftCategory;
use App\Models\Product;
use App\Models\ProductAudience;
use Illuminate\Console\Command;

/**
 * GiftCategory ("/weihnachtsgeschenke/{slug}/") and ProductAudience
 * ("/fuer/{slug}/") ended up as two near-identical taxonomies - each of the
 * three audience slugs (erwachsene/familie/kinder) has a matching gift
 * category with almost the same product set. On a product's own page this
 * showed as two "duplicate" tag links to the reader (see der-grinch, the
 * 2026-09-16 client review's example: "für die Familie" via both
 * /weihnachtsgeschenke/fuer-die-familie/ and /fuer/familie/). Detaches the
 * redundant audience wherever the matching gift category is already
 * attached to the same product - keeps the /weihnachtsgeschenke/ link
 * (Heiko's explicit "belassen"), drops the /fuer/ one. Doesn't touch
 * products that only have the audience and not the matching gift category -
 * that's not a duplicate, so there's nothing to clean up there.
 */
class DedupeProductAudienceTags extends Command
{
    protected $signature = 'content:dedupe-product-audience-tags';

    protected $description = 'Detach a ProductAudience from a product wherever its matching GiftCategory is already attached';

    /** @var array<string, string> audience slug => matching gift category slug */
    private const PAIRS = [
        'erwachsene' => 'erwachsene',
        'familie' => 'fuer-die-familie',
        'kinder' => 'fuer-kinder',
    ];

    public function handle(): int
    {
        $totalDetached = 0;

        foreach (self::PAIRS as $audienceSlug => $giftCategorySlug) {
            $audience = ProductAudience::where('slug', $audienceSlug)->first();
            $giftCategory = GiftCategory::where('slug', $giftCategorySlug)->first();

            if (! $audience || ! $giftCategory) {
                $this->warn("Skipping pair {$audienceSlug}/{$giftCategorySlug} - one side not found.");

                continue;
            }

            $giftCategoryProductIds = $giftCategory->products()->pluck('products.id');

            $overlapping = $audience->products()
                ->whereIn('products.id', $giftCategoryProductIds)
                ->pluck('products.id');

            if ($overlapping->isEmpty()) {
                continue;
            }

            $audience->products()->detach($overlapping);
            $totalDetached += $overlapping->count();
            $this->info("Detached '{$audienceSlug}' from {$overlapping->count()} product(s) that already had '{$giftCategorySlug}'.");
        }

        $this->info("Done - {$totalDetached} redundant tag(s) removed in total.");

        return self::SUCCESS;
    }
}
