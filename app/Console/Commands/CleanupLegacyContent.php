<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * One-time cleanup for a batch of legacy-content issues flagged during the
 * pre-launch review (see the marketing feedback list): the "/storage/wordpress/"
 * media folder name leaking into public URLs, an insecure http:// Amazon
 * widget iframe left over from the old site, and the outdated 2015 intro
 * text on the Geschenkideen page. Safe to re-run - every step checks for
 * its own already-done state first.
 */
class CleanupLegacyContent extends Command
{
    protected $signature = 'content:cleanup-legacy';

    protected $description = 'Rename the legacy wordpress/ media folder, drop the insecure iframe, and refresh the Geschenkideen intro';

    public function handle(): int
    {
        $this->renameWordpressFolder();
        $this->rewriteWordpressPathReferences();
        $this->removeInsecureIframe();
        $this->refreshGeschenkideenIntro();

        return self::SUCCESS;
    }

    private function renameWordpressFolder(): void
    {
        $old = Storage::disk('public')->path('wordpress');
        $new = Storage::disk('public')->path('media');

        if (! File::isDirectory($old)) {
            $this->info('wordpress/ folder already renamed, skipping.');

            return;
        }

        File::ensureDirectoryExists($new);
        File::copyDirectory($old, $new);
        File::deleteDirectory($old);
        $this->info('Renamed storage/app/public/wordpress to media.');
    }

    private function rewriteWordpressPathReferences(): void
    {
        $postCount = 0;
        Post::where('body_html', 'like', '%/storage/wordpress/%')->get()->each(function (Post $post) use (&$postCount) {
            $post->body_html = str_replace('/storage/wordpress/', '/storage/media/', $post->body_html);
            $post->saveQuietly();
            $postCount++;
        });

        $pageCount = 0;
        Page::where('body_html', 'like', '%/storage/wordpress/%')->get()->each(function (Page $page) use (&$pageCount) {
            $page->body_html = str_replace('/storage/wordpress/', '/storage/media/', $page->body_html);
            $page->saveQuietly();
            $pageCount++;
        });

        $this->info("Rewrote /storage/wordpress/ references in {$postCount} posts, {$pageCount} pages.");
    }

    private function removeInsecureIframe(): void
    {
        $post = Post::where('slug', 'die_hommingberger_gepardenforelle')->first();

        if (! $post || ! str_contains($post->body_html, 'rcm-eu.amazon-adsystem.com')) {
            $this->info('Insecure iframe already removed, skipping.');

            return;
        }

        $post->body_html = preg_replace(
            '/<iframe[^>]*rcm-eu\.amazon-adsystem\.com[^>]*><\/iframe>/i',
            '',
            $post->body_html
        );
        $post->save();
        $this->info('Removed the insecure http:// Amazon iframe.');
    }

    private function refreshGeschenkideenIntro(): void
    {
        $page = Page::where('slug', 'geschenkideen')->first();

        if (! $page) {
            $this->warn('Geschenkideen page not found, skipping.');

            return;
        }

        $old = 'Geschenke gehören zum Weihnachtsfest dazu. Etwa 250 Euro beabsichtigt jeder deutsche Bürger im Durschnitt für Weihnachtsgeschenke im Jahr 2015 auszugeben. Beliebte Weihnachtsgeschenke sind';

        if (! str_contains($page->body_html, $old)) {
            $this->info('Geschenkideen intro already refreshed, skipping.');

            return;
        }

        $new = 'Weihnachtsgeschenke gehören für viele Familien fest zum Weihnachtsfest dazu. Die Trends verändern sich dabei von Jahr zu Jahr: Neben zeitlosen Klassikern wie Büchern und Spielzeug liegen aktuell besonders praktische Technik-Gadgets, gemütliche Wohlfühl-Geschenke und kleine Erlebnisse hoch im Kurs. Ein Blick in die aktuellen <a href="https://www.amazon.de/?tag=ollisweichnac-21" target="_blank" rel="noopener noreferrer">Bestseller bei Amazon</a> lohnt sich, um schnell passende Ideen für die ganze Familie zu finden. Beliebte Weihnachtsgeschenke sind';

        $page->body_html = str_replace($old, $new, $page->body_html);
        $page->save();
        $this->info('Refreshed the Geschenkideen intro.');
    }
}
