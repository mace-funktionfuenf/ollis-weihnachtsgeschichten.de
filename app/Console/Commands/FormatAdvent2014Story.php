<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

/**
 * The 2014 Advent calendar story was imported as one unbroken wall of text -
 * WordPress's classic editor stores one paragraph per line and only wraps
 * them in <p> at render time (wpautop()), which this rebuild never
 * replicates, so all 24 days' worth of text and images ran together with no
 * paragraph breaks at all. Restores the paragraph structure the original
 * author actually wrote (one raw line = one paragraph, day-anchor markers
 * treated as forced breaks even where the source had none), and unwraps
 * each per-day illustration from its dead "view full size" link - there is
 * no lightbox script on this site (see app.blade.php), so it never did
 * anything but confuse the WordPress-era class/data-* attributes.
 *
 * The `.alignleft`/`.aligncenter` float CSS and `.amazonbutton` button
 * styling this content relies on already exist sitewide (app.blade.php) -
 * this command only fixes this one post's missing paragraph structure.
 */
class FormatAdvent2014Story extends Command
{
    protected $signature = 'content:format-advent-2014-story';

    protected $description = 'Restructure the 2014 Advent calendar story into proper paragraphs';

    public function handle(): int
    {
        // Also matched by its new slug (see content:rename-advent-stories,
        // 2026-09-16) - this command must keep working regardless of which
        // one of the two has already been run against a given environment.
        $post = Post::whereIn('slug', ['adventskalendergeschichte-2014', 'adventskalendergeschichte-durchstarter'])->first();

        if (! $post) {
            $this->warn('adventskalendergeschichte-2014/-durchstarter not found, skipping.');

            return self::SUCCESS;
        }

        if (! str_contains($post->body_html, '<a name=')) {
            // Already formatted, but an earlier run of this command could
            // still have left two image-only paragraphs adjacent with no
            // text between them (see clearImageCollisions()) - fix that in
            // place rather than re-deriving from the raw WXR content, which
            // would blow away any hand-fixed body_html since
            // import:wordpress always re-imports from the raw export.
            $blocks = explode("\n", trim($post->body_html));
            $blocks = $this->removeLeadingHeaderImage($blocks);
            $fixed = implode("\n", $this->clearImageCollisions($blocks));

            if ($fixed !== $post->body_html) {
                $post->body_html = $fixed;
                $post->save();
                $this->info('Fixed an adjacent-image float collision in the already-formatted story.');
            } else {
                $this->info('adventskalendergeschichte-durchstarter already formatted, skipping.');
            }

            return self::SUCCESS;
        }

        $html = $post->body_html;

        // Day-anchor markers no longer serve any purpose (nothing links to
        // them), but they do mark where one day's text ends and the next
        // begins - even where the source ran them together on one raw line
        // (e.g. day 7 into day 8). Turn each into a forced line break
        // before the rest of the cleanup, so paragraph splitting below
        // can't merge two different days into one paragraph.
        $html = preg_replace('/<a name="\d+"><\/a>/', "\n", $html);

        // WordPress paste artifacts and stray non-breaking-space-only
        // lines - meaningless outside WordPress.
        $html = str_replace(['<!--more-->', '<!--StartFragment -->'], '', $html);
        $html = preg_replace('/^\s*&nbsp;\s*$/m', '', $html);

        // Unwrap each per-day illustration from its dead self-link, and
        // drop the WordPress-only class/data-* cruft - keep only what
        // still means something.
        $html = preg_replace_callback(
            '#<a href="[^"]*\.jpg"[^>]*><img\s+([^>]*?)\s*/?></a>#',
            function (array $m) {
                $attrs = $m[1];
                preg_match('/class="([^"]*)"/', $attrs, $class);
                preg_match('/src="([^"]*)"/', $attrs, $src);
                preg_match('/alt="([^"]*)"/', $attrs, $alt);
                preg_match('/width="([^"]*)"/', $attrs, $width);
                preg_match('/height="([^"]*)"/', $attrs, $height);

                $align = str_contains($class[1] ?? '', 'aligncenter') ? 'aligncenter' : 'alignleft';

                return sprintf(
                    '<img class="%s" src="%s" alt="%s" width="%s" height="%s">',
                    $align,
                    $src[1] ?? '',
                    $alt[1] ?? '',
                    $width[1] ?? '',
                    $height[1] ?? ''
                );
            },
            $html
        );

        $lines = preg_split('/\n+/', trim((string) $html));

        // One button's link text itself spanned two raw source lines
        // ("Adventsgeschichten zum Vorlesen" / "- jetzt auf Amazon
        // entdecken!"), so a plain per-line split would cut its <a> tag in
        // half. Merge consecutive lines whenever the buffer has an <a>
        // opened but not yet closed, so no anchor ever ends up straddling
        // two paragraphs.
        $merged = [];
        $buffer = '';
        foreach ($lines as $line) {
            $buffer = $buffer === '' ? $line : $buffer."\n".$line;

            if (substr_count($buffer, '<a ') <= substr_count($buffer, '</a>')) {
                $merged[] = $buffer;
                $buffer = '';
            }
        }
        if ($buffer !== '') {
            $merged[] = $buffer;
        }
        $lines = $merged;

        $paragraphs = array_filter(array_map(function (string $line) {
            $line = trim($line);

            if ($line === '') {
                return null;
            }

            // Already a block-level element - don't wrap it in a <p>.
            if (preg_match('/^<h[23]>/', $line)) {
                return $line;
            }

            return '<p>'.$line.'</p>';
        }, $lines));

        $paragraphs = $this->removeLeadingHeaderImage($paragraphs);
        $post->body_html = implode("\n", $this->clearImageCollisions($paragraphs));
        $post->save();
        $this->info('Reformatted the 2014 Advent calendar story into proper paragraphs.');

        return self::SUCCESS;
    }

    /**
     * The post's opening paragraph carries a second decorative photo of the
     * felt Advent calendar (labelled "Türchen 16" in the original WordPress
     * media library) ahead of the real day-by-day sequence, which starts
     * separately at "Türchen 1" once the first day-anchor is reached later
     * in the content. "16" out of order right next to the intro reads as a
     * mistake rather than intentional header art, and it's what collided
     * with "Türchen 1" in the first place (see clearImageCollisions()) - so
     * drop just that leading image and keep the intro paragraph's own text.
     * The real "Türchen 16" photo still appears later, in its correct
     * chronological position, and is untouched by this.
     *
     * @param  array<int, string>  $blocks
     * @return array<int, string>
     */
    private function removeLeadingHeaderImage(array $blocks): array
    {
        $firstKey = array_key_first($blocks);

        if ($firstKey !== null && preg_match('/^<p><img class="alignleft"[^>]*>(.*)$/', $blocks[$firstKey], $m)) {
            $blocks[$firstKey] = '<p>'.$m[1];
        }

        return $blocks;
    }

    /**
     * Two image-only paragraphs in a row have nothing between them to force
     * a line break, so both alignleft floats sit side by side instead of
     * stacking - this squeezes whatever paragraph text follows into an
     * unreadably narrow column (one character per line). Give every
     * image-only paragraph after the first in such a run a clear-left, so
     * it drops below the previous one instead.
     *
     * @param  array<int, string>  $blocks
     * @return array<int, string>
     */
    private function clearImageCollisions(array $blocks): array
    {
        $previousWasImageOnly = false;

        foreach ($blocks as &$block) {
            $isImageOnly = (bool) preg_match('/^<p><img class="alignleft"/', $block);

            if ($isImageOnly && $previousWasImageOnly && ! str_contains($block, 'clear:')) {
                $block = preg_replace('/^<p><img class="alignleft"/', '<p><img class="alignleft" style="clear: left;"', $block, 1);
            }

            $previousWasImageOnly = $isImageOnly;
        }

        return $blocks;
    }
}
