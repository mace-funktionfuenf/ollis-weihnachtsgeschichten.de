<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\GiftCategory;
use App\Models\MediaType;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductAudience;
use App\Models\Redirect;
use App\Models\Shop;
use App\Models\Tag;
use App\Services\StaticSiteExporter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class ImportWordPress extends Command
{
    protected $signature = 'import:wordpress {path=ollisweihnachtsgeschichten.WordPress.2026-08-19.xml}';

    protected $description = 'Import the legacy WordPress WXR export into the Laravel models';

    private const SITE_ORIGIN_PATTERN = '#https?://(www\.)?ollis-weihnachtsgeschichten\.de#i';

    private const AFFILIATE_TAG = 'ollisweichnac-21';

    /** @var list<string> */
    private array $warnings = [];

    /** @var array<string, array{url: string, alt: string}> wp:post_id => attachment data */
    private array $attachments = [];

    /** @var array<string, string> author login => display name */
    private array $authors = [];

    /** @var array<string, string> asin => product slug, built once products are imported */
    private array $asinToSlug = [];

    /** @var array<string, string> asin => product title */
    private array $asinToTitle = [];

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! is_file($path)) {
            $this->error("Export not found at {$path}");

            return self::FAILURE;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);

        if ($xml === false) {
            $this->error('Failed to parse the WXR export as XML.');

            return self::FAILURE;
        }

        $ns = $xml->getNamespaces(true);
        $items = $xml->channel->item;

        Model::withoutEvents(function () use ($xml, $items, $ns) {
            $this->indexAuthors($xml, $ns);
            $this->indexAttachments($items, $ns);

            $this->importCategories($xml, $ns);
            $this->importTags($xml, $ns);
            $this->importProductTaxonomy($xml, $ns, 'fuer', ProductAudience::class);
            $this->importProductTaxonomy($xml, $ns, 'weihnachtsgeschenke', GiftCategory::class);
            $this->importProductTaxonomy($xml, $ns, 'weihnachtsgeschichten', MediaType::class);

            // Products before posts: post bodies reference products via
            // [ASA]/[produkte] shortcodes, resolved against real Product rows.
            $this->importProducts($items, $ns);
            $this->importProductRelations($items, $ns);
            $this->importShops($items, $ns);
            $this->importPosts($items, $ns);
            $this->importPages($items, $ns);
            $this->importAttachmentRedirects($items, $ns);
        });

        $this->newLine();
        $this->info('Rebuilding static export...');
        $count = count(app(StaticSiteExporter::class)->exportAll());
        $this->info("{$count} pages exported to public/cache/.");

        if ($this->warnings !== []) {
            $this->newLine();
            $this->warn(count($this->warnings).' item(s) flagged for human review:');
            foreach ($this->warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Indexing passes
    // ------------------------------------------------------------------

    /** @param array<string, string> $ns */
    private function indexAuthors(SimpleXMLElement $xml, array $ns): void
    {
        foreach ($xml->channel->children($ns['wp']) as $child) {
            if ($child->getName() !== 'author') {
                continue;
            }
            $login = (string) $child->author_login;
            $display = (string) $child->author_display_name;
            $this->authors[$login] = $display !== '' ? $display : $login;
        }
    }

    /**
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function indexAttachments(iterable $items, array $ns): void
    {
        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'attachment') {
                continue;
            }

            $meta = $this->postmeta($item, $ns);

            $this->attachments[(string) $wp->post_id] = [
                'url' => (string) $wp->attachment_url,
                'alt' => $meta['_wp_attachment_image_alt'] ?? '',
            ];
        }
    }

    // ------------------------------------------------------------------
    // Taxonomies
    // ------------------------------------------------------------------

    /** @param array<string, string> $ns */
    private function importCategories(SimpleXMLElement $xml, array $ns): void
    {
        $bySlug = [];
        $parentSlug = [];

        foreach ($xml->channel->children($ns['wp']) as $child) {
            if ($child->getName() !== 'category') {
                continue;
            }

            $slug = (string) $child->category_nicename;
            $category = Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => html_entity_decode((string) $child->cat_name)]
            );
            $bySlug[$slug] = $category;

            $parent = (string) $child->category_parent;
            if ($parent !== '') {
                $parentSlug[$slug] = $parent;
            }
        }

        foreach ($parentSlug as $slug => $parent) {
            if (isset($bySlug[$parent])) {
                $bySlug[$slug]->update(['parent_id' => $bySlug[$parent]->id]);
            }
        }
    }

    /** @param array<string, string> $ns */
    private function importTags(SimpleXMLElement $xml, array $ns): void
    {
        foreach ($xml->channel->children($ns['wp']) as $child) {
            if ($child->getName() !== 'tag') {
                continue;
            }

            Tag::updateOrCreate(
                ['slug' => (string) $child->tag_slug],
                ['name' => html_entity_decode((string) $child->tag_name)]
            );
        }
    }

    /**
     * @param  array<string, string>  $ns
     * @param  class-string<Model>  $model
     */
    private function importProductTaxonomy(SimpleXMLElement $xml, array $ns, string $taxonomy, string $model): void
    {
        foreach ($xml->channel->children($ns['wp']) as $child) {
            if ($child->getName() !== 'term') {
                continue;
            }
            if ((string) $child->term_taxonomy !== $taxonomy) {
                continue;
            }

            $model::updateOrCreate(
                ['slug' => (string) $child->term_slug],
                ['name' => html_entity_decode((string) $child->term_name)]
            );
        }
    }

    // ------------------------------------------------------------------
    // Products
    // ------------------------------------------------------------------

    /**
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function importProducts(iterable $items, array $ns): void
    {
        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'product') {
                continue;
            }

            $meta = $this->postmeta($item, $ns);
            $slug = (string) $wp->post_name;
            $label = "product /produkt/{$slug}/";

            $asin = $meta['product_shops_0_amazon_asin'] ?? $meta['amazon_produkt_id'] ?? null;
            $price = $this->toDecimal($meta['product_shops_0_price'] ?? $meta['preis'] ?? null);
            $priceOld = $this->toDecimal($meta['product_shops_0_price_old'] ?? null);
            $currency = strtoupper($meta['product_shops_0_currency'] ?? 'euro') === 'EURO' ? 'EUR' : strtoupper($meta['product_shops_0_currency'] ?? 'EUR');
            $affiliateLink = $meta['product_shops_0_link'] ?? $meta['link'] ?? null;
            $imagePath = $this->downloadProductImage($meta['_thumbnail_ext_url'] ?? null, $slug, $label);

            $product = Product::updateOrCreate(
                ['wp_post_id' => (int) $wp->post_id],
                [
                    'slug' => $slug,
                    'title' => html_entity_decode((string) $item->title),
                    'body_html' => $this->cleanHtml((string) $item->children($ns['content'])->encoded, $label, false),
                    'asin' => $asin,
                    'ean' => $meta['product_ean'] ?? null,
                    'article_number' => $meta['product_shops_0_artnr'] ?? null,
                    'price' => $price,
                    'price_old' => $priceOld,
                    'currency' => $currency ?: 'EUR',
                    'portal' => $meta['product_shops_0_portal'] ?? null,
                    'affiliate_link' => $affiliateLink,
                    'rating' => $this->toInt($meta['product_rating'] ?? null),
                    'rating_count' => $this->toInt($meta['product_rating_cnt'] ?? null),
                    'available' => ($meta['product_not_avail'] ?? '0') !== '1',
                    'image_path' => $imagePath,
                    'published_at' => $this->toDateTime((string) $wp->post_date),
                    'meta_description' => $meta['_yoast_wpseo_metadesc'] ?? null,
                ]
            );

            if ($asin) {
                $this->asinToSlug[$asin] = $slug;
                $this->asinToTitle[$asin] = $product->title;
            }

            $this->attachProductTaxonomy($item, $product, 'fuer', ProductAudience::class, 'audiences');
            $this->attachProductTaxonomy($item, $product, 'weihnachtsgeschenke', GiftCategory::class, 'giftCategories');
            $this->attachProductTaxonomy($item, $product, 'weihnachtsgeschichten', MediaType::class, 'mediaTypes');
        }
    }

    /** @param class-string<Model> $model */
    private function attachProductTaxonomy(SimpleXMLElement $item, Product $product, string $taxonomy, string $model, string $relation): void
    {
        $slugs = [];
        foreach ($item->category as $category) {
            if ((string) $category['domain'] === $taxonomy) {
                $slugs[] = (string) $category['nicename'];
            }
        }

        if ($slugs === []) {
            return;
        }

        $ids = $model::whereIn('slug', $slugs)->pluck('id');
        $product->{$relation}()->sync($ids);
    }

    /**
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function importProductRelations(iterable $items, array $ns): void
    {
        $wpIdToProductId = Product::pluck('id', 'wp_post_id');

        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'product') {
                continue;
            }

            $meta = $this->postmeta($item, $ns);
            $raw = $meta['product_related'] ?? null;

            if (! $raw) {
                continue;
            }

            $relatedWpIds = $this->unserializeMeta($raw) ?? [];
            $relatedIds = collect($relatedWpIds)
                ->map(fn ($wpId) => $wpIdToProductId[(int) $wpId] ?? null)
                ->filter()
                ->values();

            if ($relatedIds->isEmpty()) {
                continue;
            }

            $productId = $wpIdToProductId[(int) $wp->post_id] ?? null;
            if ($productId) {
                Product::find($productId)?->related()->sync($relatedIds);
            }
        }
    }

    private function downloadProductImage(?string $url, string $slug, string $label): ?string
    {
        if (! $url) {
            return null;
        }

        $relativePath = 'products/'.$slug.'.'.$this->extensionFromUrl($url);

        return $this->downloadTo($url, $relativePath, $label);
    }

    /**
     * Posts reference their featured image indirectly via "_thumbnail_id",
     * a WP attachment post ID - resolved against the attachment map built
     * by indexAttachments(), unlike products which had a ready-made URL.
     */
    private function downloadPostImage(?string $thumbnailId, string $slug, string $label): ?string
    {
        if (! $thumbnailId) {
            return null;
        }

        $url = $this->attachments[$thumbnailId]['url'] ?? null;

        if (! $url) {
            return null;
        }

        $relativePath = 'posts/'.$slug.'.'.$this->extensionFromUrl($url);

        return $this->downloadTo($url, $relativePath, $label);
    }

    // ------------------------------------------------------------------
    // Shops
    // ------------------------------------------------------------------

    /**
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function importShops(iterable $items, array $ns): void
    {
        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'shop' || (string) $wp->status !== 'publish') {
                continue;
            }

            $slug = (string) $wp->post_name;
            if ($slug === '') {
                continue;
            }

            $meta = $this->postmeta($item, $ns);

            Shop::updateOrCreate(
                ['wp_post_id' => (int) $wp->post_id],
                [
                    'slug' => $slug,
                    'title' => html_entity_decode((string) $item->title),
                    'widget_title' => $meta['shop_widget_title'] ?? null,
                    'widget_content' => $meta['shop_widget_content'] ?? null,
                ]
            );
        }
    }

    // ------------------------------------------------------------------
    // Posts
    // ------------------------------------------------------------------

    /**
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function importPosts(iterable $items, array $ns): void
    {
        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'post') {
                continue;
            }

            $meta = $this->postmeta($item, $ns);
            $slug = (string) $wp->post_name;
            $label = "post /{$slug}/";
            $content = (string) $item->children($ns['content'])->encoded;
            $excerptRaw = (string) ($item->children($ns['excerpt'])->encoded ?? '');
            $login = (string) $item->children($ns['dc'])->creator;
            $featuredImage = $this->downloadPostImage($meta['_thumbnail_id'] ?? null, $slug, $label);

            $post = Post::updateOrCreate(
                ['wp_post_id' => (int) $wp->post_id],
                [
                    'slug' => $slug,
                    'title' => html_entity_decode((string) $item->title),
                    'excerpt' => $excerptRaw !== '' ? trim(strip_tags($excerptRaw)) : null,
                    'body_html' => $this->cleanHtml($content, $label, true),
                    'featured_image' => $featuredImage,
                    'author_name' => $this->authors[$login] ?? $login,
                    'published_at' => $this->toDateTime((string) $wp->post_date),
                    'status' => (string) $wp->status,
                    'meta_description' => $meta['_yoast_wpseo_metadesc'] ?? null,
                ]
            );

            $categorySlugs = [];
            $tagSlugs = [];
            foreach ($item->category as $category) {
                $domain = (string) $category['domain'];
                if ($domain === 'category') {
                    $categorySlugs[] = (string) $category['nicename'];
                } elseif ($domain === 'post_tag') {
                    $tagSlugs[] = (string) $category['nicename'];
                }
            }

            $post->categories()->sync(Category::whereIn('slug', $categorySlugs)->pluck('id'));
            $post->tags()->sync(Tag::whereIn('slug', $tagSlugs)->pluck('id'));
        }
    }

    // ------------------------------------------------------------------
    // Pages
    // ------------------------------------------------------------------

    /**
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function importPages(iterable $items, array $ns): void
    {
        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'page' || (string) $wp->status !== 'publish') {
                continue;
            }

            $slug = (string) $wp->post_name;
            $label = "page /{$slug}/";

            // "weihnachtsgeschenke" is an empty WP taxonomy-archive stub on
            // the legacy site (verified against the live sitemap) - the real
            // page there is now GiftCategoryController@index, not this row.
            // "impressum" carried nothing but eRecht24 placeholder shortcodes
            // in the export (no static legal text). Real Impressum and
            // Datenschutz text has since been commissioned and is maintained
            // directly as two separate Page rows in the CMS - re-running this
            // import must never overwrite that with the placeholder again.
            if (in_array($slug, ['weihnachtsgeschenke', 'impressum'], true)) {
                continue;
            }

            $meta = $this->postmeta($item, $ns);
            $content = (string) $item->children($ns['content'])->encoded;

            Page::updateOrCreate(
                ['wp_post_id' => (int) $wp->post_id],
                [
                    'slug' => $slug,
                    'title' => html_entity_decode((string) $item->title),
                    'body_html' => $this->cleanHtml($content, $label, true),
                    'meta_description' => $meta['_yoast_wpseo_metadesc'] ?? null,
                ]
            );
        }
    }

    // ------------------------------------------------------------------
    // Redirects
    // ------------------------------------------------------------------

    /**
     * WordPress gives every attachment its own page, usually nested under
     * its parent post (e.g. /die-lustigsten-weihnachtsfilme/santa_clause.../)
     * - none of that exists as real content here, so each one becomes a 301
     * to wherever the attachment's image now actually lives, per the
     * migration skill's "redirect surface is 3-6x the visible content"
     * guidance.
     *
     * @param  iterable<SimpleXMLElement>  $items
     * @param  array<string, string>  $ns
     */
    private function importAttachmentRedirects(iterable $items, array $ns): void
    {
        $postUrls = Post::pluck('slug', 'wp_post_id')->map(fn ($slug) => "/{$slug}/");
        $pageUrls = Page::pluck('slug', 'wp_post_id')->map(fn ($slug) => "/{$slug}/");
        $productUrls = Product::pluck('slug', 'wp_post_id')->map(fn ($slug) => "/produkt/{$slug}/");

        foreach ($items as $item) {
            $wp = $item->children($ns['wp']);

            if ((string) $wp->post_type !== 'attachment') {
                continue;
            }

            $link = (string) $item->link;
            $fromPath = '/'.trim((string) parse_url($link, PHP_URL_PATH), '/');
            if ($fromPath === '/') {
                continue;
            }

            $parentId = (int) $wp->post_parent;
            $toPath = $postUrls[$parentId] ?? $pageUrls[$parentId] ?? $productUrls[$parentId] ?? '/';

            if ($fromPath === $toPath) {
                continue;
            }

            Redirect::updateOrCreate(['from_path' => $fromPath], ['to_path' => $toPath]);
        }
    }

    // ------------------------------------------------------------------
    // HTML / shortcode cleaning
    // ------------------------------------------------------------------

    private function cleanHtml(string $html, string $label, bool $resolveShortcodes): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<noscript\b[^>]*>.*?<\/noscript>/is', '', $html) ?? $html;

        if ($resolveShortcodes) {
            $html = $this->resolveShortcodes($html, $label);
        }

        $html = $this->rewriteCaption($html);
        $html = $this->rewriteUploads($html, $label);
        $html = preg_replace(self::SITE_ORIGIN_PATTERN, '', $html) ?? $html;

        $brCount = substr_count($html, '<br');
        if ($brCount > 20) {
            $this->warnings[] = "{$label}: {$brCount} <br> tags - paragraph-break heuristic needs a manual check";
        }
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', '</p><p>', $html) ?? $html;

        return trim($html);
    }

    private function resolveShortcodes(string $html, string $label): string
    {
        // [wpsleep start=".." end=".."]...[/wpsleep] - a scheduled-reveal
        // wrapper. All dates in this export are years in the past, so this
        // is resolved once, mechanically, against "now": end-gated content
        // whose window already closed is dropped (exactly what the plugin
        // itself would render today); start-only content whose start has
        // already passed is unwrapped and kept.
        $html = preg_replace_callback(
            '/\[wpsleep([^\]]*)\](.*?)\[\/wpsleep\]/is',
            function (array $m) use ($label) {
                $attrs = $this->shortcodeAttrs($m[1]);
                $start = isset($attrs['start']) ? $this->parseWpsleepDate($attrs['start']) : null;
                $end = isset($attrs['end']) ? $this->parseWpsleepDate($attrs['end']) : null;
                $now = now();

                if ($end && $now->greaterThan($end)) {
                    $this->warnings[] = "{$label}: [wpsleep] block past its end date ({$attrs['end']}) - dropped as expired";

                    return '';
                }
                if ($start && $now->lessThan($start)) {
                    $this->warnings[] = "{$label}: [wpsleep] block not yet within its start date ({$attrs['start']}) - dropped";

                    return '';
                }

                return $m[2];
            },
            $html
        ) ?? $html;

        // [ASA]ASIN[/ASA] - link to the internal product page if the ASIN
        // matches an imported product, else a plain Amazon link built the
        // same way WordPress' own product_shops_0_link values are shaped.
        $html = preg_replace_callback(
            '/\[ASA\]([A-Z0-9]+)\[\/ASA\]/i',
            function (array $m) {
                $asin = $m[1];
                if (isset($this->asinToSlug[$asin])) {
                    $title = e($this->asinToTitle[$asin]);

                    return '<a href="/produkt/'.$this->asinToSlug[$asin].'/">'.$title.'</a>';
                }

                $link = $this->amazonLink($asin);

                return '<a href="'.$link.'" rel="nofollow sponsored noopener" target="_blank">Auf Amazon ansehen</a>';
            },
            $html
        ) ?? $html;

        // [produkte ...] - backed by real Product rows; baked into static
        // product-card markup at import time (no live shortcode renderer).
        $html = preg_replace_callback(
            '/\[produkte([^\]]*)\]/i',
            fn (array $m) => $this->renderProductGrid($this->shortcodeAttrs($m[1]), $label),
            $html
        ) ?? $html;

        // [caption ...]...[/caption] handled separately in rewriteCaption().

        // [mapsmarker ...] - map plugin's own pin data isn't in the WXR
        // export at all; unrecoverable, flagged for a human pass.
        $html = preg_replace_callback('/\[mapsmarker[^\]]*\]/i', function () use ($label) {
            $this->warnings[] = "{$label}: [mapsmarker] embed removed - map data isn't in the WXR export, needs manual rebuild if still wanted";

            return '<p><em>[Hinweis: Eine interaktive Karte wurde hier entfernt.]</em></p>';
        }, $html) ?? $html;

        // [embed]URL[/embed] - real URL preserved as a plain outbound link
        // instead of a live, autoloading iframe.
        $html = preg_replace_callback('/\[embed\](.*?)\[\/embed\]/is', function (array $m) use ($label) {
            $url = trim($m[1]);
            $this->warnings[] = "{$label}: [embed] converted to a plain link ({$url}) - was previously a live iframe embed";

            return '<p><a href="'.e($url).'" rel="noopener" target="_blank">Video ansehen</a></p>';
        }, $html) ?? $html;

        // [borlabs-cookie ...] - a consent-banner button; moot under the
        // zero-banner decision.
        $html = preg_replace('/\[\/?borlabs-cookie[^\]]*\]/i', '', $html) ?? $html;

        // [erecht24 ...] - Impressum/Datenschutz text is 100% plugin
        // generated with zero static source in the export. Not fabricated.
        $html = preg_replace_callback('/\[erecht24([^\]]*)\]/i', function (array $m) use ($label) {
            $attrs = $this->shortcodeAttrs($m[1]);
            $kind = $attrs['type'] ?? 'legal';
            $this->warnings[] = "{$label}: [erecht24 type=\"{$kind}\"] has no static source - legal text must be commissioned";

            $what = $kind === 'privacy_policy' ? 'Datenschutztext' : 'Impressum-Text';

            return '<div class="flag"><strong>Hinweis:</strong> Es liegt aus dem WordPress-Export kein '.$what.
                ' vor (zuvor automatisch per eRecht24-Plugin erzeugt). Bitte aktuellen Text anfordern und hier einfügen.</div>';
        }, $html) ?? $html;

        return $html;
    }

    /** @param array<string, string> $attrs */
    private function renderProductGrid(array $attrs, string $label): string
    {
        $query = Product::query();

        if (isset($attrs['include'])) {
            $wpIds = array_filter(array_map('trim', explode(',', $attrs['include'])));
            $products = Product::whereIn('wp_post_id', $wpIds)->get()->sortBy(
                fn ($p) => array_search((string) $p->wp_post_id, $wpIds)
            );
        } else {
            if (isset($attrs['fuer'])) {
                $query->whereHas('audiences', fn ($q) => $q->where('slug', $attrs['fuer']));
            }
            if (isset($attrs['weihnachtsgeschenke'])) {
                $query->whereHas('giftCategories', fn ($q) => $q->where('slug', $attrs['weihnachtsgeschenke']));
            }
            if (isset($attrs['weihnachtsgeschichten'])) {
                $query->whereHas('mediaTypes', fn ($q) => $q->where('slug', $attrs['weihnachtsgeschichten']));
            }

            $query->orderByDesc('published_at');

            $limit = (int) ($attrs['limit'] ?? 0);
            if ($limit > 0) {
                $query->limit($limit);
            }

            $products = $query->get();
        }

        if ($products->isEmpty()) {
            $this->warnings[] = "{$label}: [produkte] shortcode resolved to zero products - check its filters by hand";

            return '';
        }

        $html = '<ul class="card-grid">';
        foreach ($products as $product) {
            $html .= $this->renderProductCard($product);
        }
        $html .= '</ul>';

        return $html;
    }

    private function renderProductCard(Product $product): string
    {
        $title = e($product->title);
        $url = $product->url();
        $image = $product->image_path
            ? '<img src="/storage/'.e($product->image_path).'" alt="'.$title.'" loading="lazy" width="160">'
            : '';
        $price = $product->price
            ? '<p class="price">'.number_format((float) $product->price, 2, ',', '.').' €</p>'
            : '';
        $buy = $product->affiliate_link
            ? '<a class="btn" href="'.e($product->affiliate_link).'" rel="nofollow sponsored noopener" target="_blank">Ansehen</a>'
            : '';

        return '<li class="card">'.$image.'<h3><a href="'.$url.'">'.$title.'</a></h3>'.$price.
            '<p><a class="btn secondary" href="'.$url.'">Details</a> '.$buy.'</p></li>';
    }

    private function rewriteCaption(string $html): string
    {
        return preg_replace_callback(
            '/\[caption[^\]]*\](.*?)\[\/caption\]/is',
            function (array $m) {
                $inner = trim($m[1]);

                if (preg_match('/^(.*<\/a>|.*?\/>)(.*)$/is', $inner, $parts)) {
                    $media = $parts[1];
                    $captionText = trim($parts[2], " \t\n\r\0\x0B\xC2\xA0");

                    return $captionText !== ''
                        ? '<figure>'.$media.'<figcaption>'.$captionText.'</figcaption></figure>'
                        : '<figure>'.$media.'</figure>';
                }

                return '<figure>'.$inner.'</figure>';
            },
            $html
        ) ?? $html;
    }

    /**
     * Rewrite same-domain wp-content/uploads references to the local,
     * self-hosted copy - and actually download it. Everything else
     * pointing at the site's own domain is left for the origin-strip
     * pass in cleanHtml() to turn into a plain relative path.
     */
    private function rewriteUploads(string $html, string $label): string
    {
        return preg_replace_callback(
            '#https?://(?:www\.)?ollis-weihnachtsgeschichten\.de/wp-content/uploads/([^"\'\s)]+)#i',
            function (array $m) use ($label) {
                $relative = 'wordpress/'.$m[1];
                $stored = $this->downloadTo('https://www.ollis-weihnachtsgeschichten.de/wp-content/uploads/'.$m[1], $relative, $label);

                return $stored ? '/storage/'.$stored : $m[0];
            },
            $html
        ) ?? $html;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** @param array<string, string> $ns @return array<string, string> */
    private function postmeta(SimpleXMLElement $item, array $ns): array
    {
        $wp = $item->children($ns['wp']);
        $meta = [];

        foreach ($wp->postmeta as $entry) {
            $meta[(string) $entry->meta_key] = (string) $entry->meta_value;
        }

        return $meta;
    }

    /** @return array<mixed>|null */
    private function unserializeMeta(string $raw): ?array
    {
        $data = @unserialize($raw, ['allowed_classes' => false]);

        return is_array($data) ? $data : null;
    }

    /** @return array<string, string> */
    private function shortcodeAttrs(string $raw): array
    {
        preg_match_all('/([a-z_]+)="([^"]*)"/i', $raw, $matches, PREG_SET_ORDER);
        $attrs = [];
        foreach ($matches as $match) {
            $attrs[$match[1]] = $match[2];
        }

        return $attrs;
    }

    private function parseWpsleepDate(string $raw): ?\Illuminate\Support\Carbon
    {
        foreach (['d.m.Y H:i', 'd.m.Y'] as $format) {
            try {
                return \Illuminate\Support\Carbon::createFromFormat($format, $raw);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function amazonLink(string $asin): string
    {
        return 'https://www.amazon.de/dp/'.$asin.'?tag='.self::AFFILIATE_TAG.'&linkCode=ogi&th=1&psc=1';
    }

    private function toDecimal(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function toInt(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function toDateTime(string $wpDate): ?\Illuminate\Support\Carbon
    {
        if ($wpDate === '' || str_starts_with($wpDate, '0000-00-00')) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($wpDate);
    }

    private function extensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : 'jpg';
    }

    private function downloadTo(string $url, string $relativePath, string $label): ?string
    {
        if (Storage::disk('public')->exists($relativePath)) {
            return $relativePath;
        }

        try {
            // The live host's WAF resets connections from Guzzle's default
            // User-Agent but accepts a normal browser one.
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            ])->timeout(30)->get($url);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            Storage::disk('public')->put($relativePath, $response->body());

            $newBasename = \App\Support\ImageOptimizer::optimize(Storage::disk('public')->path($relativePath));

            if ($newBasename) {
                $relativePath = dirname($relativePath).'/'.$newBasename;
            }

            return $relativePath;
        } catch (\Throwable $e) {
            $this->warnings[] = "{$label}: image download failed ({$url}): {$e->getMessage()}";

            return null;
        }
    }
}
