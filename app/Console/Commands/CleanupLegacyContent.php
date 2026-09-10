<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * One-time cleanup for a batch of legacy-content issues flagged during the
 * pre-launch review (see the marketing feedback list): the "/storage/wordpress/"
 * media folder name leaking into public URLs, an insecure http:// Amazon
 * widget iframe left over from the old site, the outdated 2015 intro text
 * on the Geschenkideen page, and that same page's static product-card HTML
 * (a leftover WordPress shortcode resolution) still linking to thin/removed
 * product pages, showing unavailable products, and carrying a dead AWIN
 * banner ad. Safe to re-run - every step checks for its own already-done
 * state first.
 */
class CleanupLegacyContent extends Command
{
    protected $signature = 'content:cleanup-legacy';

    protected $description = 'Rename the legacy wordpress/ media folder, drop the insecure iframe, and clean up the Geschenkideen page';

    public function handle(): int
    {
        $this->renameWordpressFolder();
        $this->rewriteWordpressPathReferences();
        $this->removeInsecureIframe();
        $this->refreshGeschenkideenIntro();
        $this->rewriteGeschenkideenTrendsIntro();
        $this->cleanupGeschenkideenProductCards();

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

    /**
     * The intro block still carried, below its first two sentences, a
     * WordPress-era tracking-link bullet list (Amazon category links with
     * ancient pf_rd_* affiliate parameters), two off-topic external sites
     * over plain http://, and a filler bullet list that didn't even link to
     * the sections it named. Replaces the whole block with a short,
     * current-trends paragraph per the 2026-09 client review (point 2) -
     * the weekly auto-refresh the client also asked for is a separate,
     * bigger decision (needs an LLM API + a scheduled job) and isn't part
     * of this one-off content fix.
     */
    private function rewriteGeschenkideenTrendsIntro(): void
    {
        $page = Page::where('slug', 'geschenkideen')->first();

        if (! $page || ! str_contains($page->body_html, 'homerobot24.de')) {
            $this->info('Geschenkideen trends intro already rewritten, skipping.');

            return;
        }

        $marker = '<h2>Geschenkideen für Kinder</h2>';
        $splitAt = strpos($page->body_html, $marker);

        if ($splitAt === false) {
            $this->warn('Geschenkideen product sections not found, skipping trends intro rewrite.');

            return;
        }

        $intro = 'Weihnachtsgeschenke gehören für viele Familien fest zum Weihnachtsfest dazu. '
            .'Die Trends verändern sich dabei von Jahr zu Jahr: Neben zeitlosen Klassikern wie '
            .'Büchern und Spielzeug liegen aktuell besonders praktische Technik-Gadgets, gemütliche '
            .'Wohlfühl-Geschenke und kleine Erlebnisse hoch im Kurs. Auch personalisierte Geschenke '
            .'und nachhaltig produziertes Spielzeug werden immer beliebter. Ein Blick in die '
            .'aktuellen <a href="https://www.amazon.de/?tag=ollisweichnac-21" target="_blank" '
            .'rel="noopener noreferrer">Amazon-Bestseller zu Weihnachten</a> lohnt sich, um schnell '
            .'passende Ideen zu finden. Hier ein paar unserer Favoriten für Kinder, Erwachsene und '
            .'die ganze Familie:'."\n";

        $page->body_html = $intro.substr($page->body_html, $splitAt);
        $page->save();
        $this->info('Rewrote the Geschenkideen trends intro.');
    }

    /**
     * The Geschenkideen page still carries static HTML resolved from the
     * old WordPress [produkte] shortcode: each card links both to an
     * internal product page and directly to Amazon, with no regard for
     * whether the product is still available, plus a dead "1a-Geschenkeshop"
     * AWIN banner. Brings it in line with the site-wide convention already
     * used by <x-product-card>: no internal product link, unavailable
     * products dropped, and the Amazon button labelled "Werbung: ..." per
     * the affiliate-disclosure requirement.
     */
    private function cleanupGeschenkideenProductCards(): void
    {
        $page = Page::where('slug', 'geschenkideen')->first();

        if (! $page || (! str_contains($page->body_html, 'START ADVERTISER') && ! preg_match('#<a[^>]+href="/produkt/#', $page->body_html))) {
            $this->info('Geschenkideen product cards already cleaned up, skipping.');

            return;
        }

        $html = preg_replace('/<!-- START ADVERTISER:.*?END ADVERTISER:[^>]*-->/s', '', $page->body_html);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $availableSlugs = Product::where('available', true)->pluck('slug')->all();

        foreach (iterator_to_array($dom->getElementsByTagName('li')) as $li) {
            if (! str_contains($li->getAttribute('class'), 'card')) {
                continue;
            }

            $slug = null;
            $detailsLink = null;
            $internalLinks = [];

            foreach (iterator_to_array($li->getElementsByTagName('a')) as $a) {
                if (preg_match('#^/produkt/([a-z0-9\-]+)/$#', $a->getAttribute('href'), $m)) {
                    $slug = $m[1];
                    $internalLinks[] = $a;
                    if ($a->textContent === 'Details') {
                        $detailsLink = $a;
                    }
                } elseif (str_contains($a->getAttribute('href'), 'amazon.de') && $a->textContent === 'Ansehen') {
                    $a->textContent = 'Werbung: Details bei Amazon';
                }
            }

            if ($slug !== null && ! in_array($slug, $availableSlugs, true)) {
                $li->parentNode->removeChild($li);

                continue;
            }

            if ($detailsLink) {
                $trailingSpace = $detailsLink->nextSibling;
                $detailsLink->parentNode->removeChild($detailsLink);
                if ($trailingSpace && $trailingSpace->nodeType === XML_TEXT_NODE) {
                    $trailingSpace->parentNode->removeChild($trailingSpace);
                }
            }

            // The <h3> title link is a second, separate internal link to the
            // same thin/unavailable-gated product page - matching
            // <x-product-card>, the title stays but stops being a link.
            foreach ($internalLinks as $a) {
                if ($a === $detailsLink || ! $a->parentNode) {
                    continue;
                }

                $text = $dom->createTextNode($a->textContent);
                $a->parentNode->replaceChild($text, $a);
            }
        }

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $result = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $result .= $dom->saveHTML($child);
        }

        $page->body_html = $result;
        $page->save();
        $this->info('Cleaned up Geschenkideen product cards: dropped unavailable products, internal Details links, and the AWIN banner.');
    }
}
