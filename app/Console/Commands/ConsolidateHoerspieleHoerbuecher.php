<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * "/weihnachtshoerspiele-fuer-kinder/" and "/weihnachtshoerbuecher-fuer-kinder/"
 * were both imported as ordinary Posts with static, import-time-baked
 * product HTML - the first matched an overly restrictive [produkte]
 * shortcode (MediaType AND ProductAudience AND GiftCategory all at once,
 * see ImportWordPress::renderProductGrid()), so only one card ever
 * rendered; the second had no shortcode at all, just four hand-typed, now
 * stale Amazon links with no connection to the Product model. The nav
 * already links to a page that covers exactly this content correctly -
 * "/die-schoensten-weihnachtsgeschichten/weihnachtsgeschichten-als-hoerbuch/"
 * (see CategoryController::MEDIA_TYPES_BY_CATEGORY_SLUG) - so rather than
 * fix two dead ends separately, redirect both there and stop routing to
 * the old Posts. Unpublishes rather than deletes the two Posts: reversible,
 * and routes/web.php's catch-all already skips non-"publish" posts, which
 * is what lets the Redirect actually take effect instead of the Post
 * lookup matching first.
 */
class ConsolidateHoerspieleHoerbuecher extends Command
{
    protected $signature = 'content:consolidate-hoerspiele-hoerbuecher';

    protected $description = 'Unpublish the legacy Hörspiele/Hörbücher posts and redirect both to the combined category page';

    private const TARGET = '/die-schoensten-weihnachtsgeschichten/weihnachtsgeschichten-als-hoerbuch';

    /** @var list<string> */
    private const LEGACY_SLUGS = [
        'weihnachtshoerspiele-fuer-kinder',
        'weihnachtshoerbuecher-fuer-kinder',
    ];

    public function handle(): int
    {
        foreach (self::LEGACY_SLUGS as $slug) {
            $post = Post::where('slug', $slug)->first();

            if ($post && $post->status === 'publish') {
                $post->status = 'draft';
                $post->save();
                $this->info("Unpublished post '{$slug}'.");
            } elseif ($post) {
                $this->info("Post '{$slug}' already unpublished, skipping.");
            } else {
                $this->warn("Post '{$slug}' not found, skipping unpublish step.");
            }

            $fromPath = '/'.$slug;

            $redirect = Redirect::firstOrNew(['from_path' => $fromPath]);
            $redirect->to_path = self::TARGET;
            $redirect->status_code = 301;

            if ($redirect->isDirty()) {
                $redirect->save();
                $this->info("Redirected '{$fromPath}' -> '".self::TARGET."'.");
            } else {
                $this->info("Redirect for '{$fromPath}' already up to date, skipping.");
            }
        }

        return self::SUCCESS;
    }
}
