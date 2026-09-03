<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;

/**
 * Renders rich-text body HTML (post/page/product/shop bodies, wherever it's
 * imported from WordPress or authored fresh in the RichEditor): every
 * external link opens in a new tab, and heading levels are normalized so
 * they never skip a level below the page's own <h1> title - a lot of
 * imported WordPress content jumps straight to <h3> (or deeper) with no
 * <h2> in between, which breaks the page's semantic outline for assistive
 * technology even though it looks fine visually. Applied at render time
 * rather than baked into stored HTML, so it covers old imported content and
 * anything written in Filament identically, with one rule to keep in sync.
 */
class ContentHtml
{
    /** @var list<string> */
    private const INTERNAL_HOSTS = [
        'ollis-weihnachtsgeschichten.de',
        'www.ollis-weihnachtsgeschichten.de',
    ];

    public static function externalLinksInNewTab(?string $html): string
    {
        if (! $html || trim($html) === '') {
            return (string) $html;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        self::fixExternalLinks($dom);
        self::normalizeHeadingLevels($dom);

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $result = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    private static function fixExternalLinks(DOMDocument $dom): void
    {
        $internalHosts = self::INTERNAL_HOSTS;
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($appHost) {
            $internalHosts[] = $appHost;
        }

        foreach (iterator_to_array($dom->getElementsByTagName('a')) as $link) {
            $href = $link->getAttribute('href');

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            $host = parse_url($href, PHP_URL_HOST);
            $isInternal = $host === null || in_array($host, $internalHosts, true);

            if ($isInternal) {
                continue;
            }

            $link->setAttribute('target', '_blank');

            $rel = array_unique(array_filter([
                ...preg_split('/\s+/', $link->getAttribute('rel'), -1, PREG_SPLIT_NO_EMPTY),
                'noopener',
                'noreferrer',
            ]));
            $link->setAttribute('rel', implode(' ', $rel));
        }
    }

    /**
     * The page itself always owns <h1> for the title, so content headings
     * should start no higher than <h2>. Shifts every heading in the
     * fragment by a constant offset so the shallowest one found becomes
     * <h2>, preserving whatever relative nesting the author already used
     * (h3 nested under h4 stays nested, just two levels shallower).
     */
    private static function normalizeHeadingLevels(DOMDocument $dom): void
    {
        $headings = [];
        foreach (range(1, 6) as $level) {
            foreach (iterator_to_array($dom->getElementsByTagName('h'.$level)) as $node) {
                $headings[] = [$level, $node];
            }
        }

        if ($headings === []) {
            return;
        }

        $minLevel = min(array_column($headings, 0));
        $offset = 2 - $minLevel;

        if ($offset === 0) {
            return;
        }

        foreach ($headings as [$level, $node]) {
            $newLevel = max(2, min(6, $level + $offset));
            $replacement = $dom->createElement('h'.$newLevel);

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $replacement->setAttribute($attribute->name, $attribute->value);
            }

            foreach (iterator_to_array($node->childNodes) as $child) {
                $replacement->appendChild($child);
            }

            $node->parentNode->replaceChild($replacement, $node);
        }
    }
}
