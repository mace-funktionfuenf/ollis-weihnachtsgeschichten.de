<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;

/**
 * Renders rich-text body HTML (post/page/product/shop bodies, wherever it's
 * imported from WordPress or authored fresh in the RichEditor) with every
 * external link opening in a new tab. Applied at render time rather than
 * baked into stored HTML, so it covers old imported content and anything
 * written in Filament identically, with one rule to keep in sync.
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

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $result = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }
}
