# CLAUDE.md — ollis-weihnachtsgeschichten.de

> Project-specific conventions. Global preferences, hard rules, and security defaults
> live in `E:\Obsidian\Battlestation\Battlestation\CLAUDE.md` and are loaded automatically — don't repeat them here.
> This file is for what's true about *this* project only.

## What this is

A Laravel rebuild of a legacy WordPress site (German, humorous Christmas stories since
2000, plus an Amazon-affiliate gift/product catalogue). Laravel + Filament is the
authoring backend (DB-backed); visitors are served **pre-rendered static HTML** from
`public/cache/**/index.html`, not a per-request Blade render — see `public/.htaccess`
and `app/Services/StaticSiteExporter.php`. Full architecture/decisions in the migration
plan this was built from (ask if you need the history — not repeated here).

## Stack & commands

- Language / framework: PHP 8.4, Laravel 11, Filament 3 (admin panel at `/admin`), SQLite locally.
- Install: `composer install`
- Local server: `php artisan serve` (does **not** exercise `public/.htaccess` — the
  static-cache rewrite only applies under real Apache)
- Test: `vendor/bin/phpunit` (or `php artisan test`)
- Re-import from the WXR export: `php -d memory_limit=512M artisan import:wordpress`
  (idempotent — safe to re-run; upserts by `wp_post_id`, skips already-downloaded images)
- Regenerate the static export by hand: `php artisan export:static` (also runs
  automatically on every Filament save via `App\Observers\StaticExportObserver`)

## Structure

- `app/Console/Commands/ImportWordPress.php` — the WXR→Eloquent import, including all
  shortcode-resolution judgment calls (see its doc comments for the reasoning per
  shortcode: `[produkte]`, `[ASA]`, `[wpsleep]`, `[caption]`, `[mapsmarker]`, `[embed]`,
  `[erecht24]`, `[borlabs-cookie]`).
- `app/Services/StaticSiteExporter.php` — renders every route via its Controller (plain
  method call, not an HTTP round-trip) and writes the HTML to `public/cache/`.
- `routes/web.php` — legacy URL shapes are preserved deliberately: posts/pages/flat
  categories share bare `/{slug}/`, products live under `/produkt/`, product-taxonomy
  archives under `/fuer/`, `/weihnachtsgeschenke/`, `/weihnachtsgeschichten/` (the last one
  is *also* a real post category root — both are genuine, see the route comments).
- `app/Models/` — one model per WordPress post type/taxonomy actually found in the
  export (`Post`, `Page`, `Product`, `Shop`, `Category`, `Tag`, `ProductAudience`,
  `GiftCategory`, `MediaType`) plus `Redirect` for legacy-URL 301s.
- `resources/views/components/layouts/app.blade.php` — the only layout; plain
  hand-written CSS, no framework/CDN (GDPR self-hosting).

## Conventions

- `wp_post_id` columns are **nullable** — only WXR-imported rows have one; content
  created fresh in Filament doesn't need it.
- Slugs are never re-derived from titles on imported content (`wp:post_name` verbatim).
- No consent banner, by design: the Twitter embed and Amazon widget `<script>`s from the
  legacy site were dropped entirely rather than ported, so there's nothing to consent to.

## Gotchas

- The live site's WAF resets connections from Guzzle's default User-Agent — any future
  outbound HTTP call to the old domain needs a browser-like `User-Agent` header (see
  `downloadTo()` in `ImportWordPress.php`) or it'll fail with `cURL error 56`.
- Blade anonymous components must live under `resources/views/components/`, not
  `resources/views/partials/` — `<x-product-card>` resolves to
  `components/product-card.blade.php`.
- `Filament\Http\Middleware\Authenticate` 403s any user that doesn't implement
  `FilamentUser`, *unless* `APP_ENV=local` — `App\Models\User` implements it explicitly
  (`canAccessPanel()` always `true`, single-editor site) so this doesn't silently break
  outside local/testing envs.
- `public/cache/` is a fully disposable build artifact (gitignored) — never hand-edit
  files in it, edit content in Filament and let the observer/export command regenerate it.

## Outstanding (from the migration — see the final handoff summary for full detail)

- A handful of imported posts are flagged (see `ImportWordPress`'s warning output) for a
  human read-through: `<br>`-heavy paragraph breaks, a removed `[mapsmarker]` embed, a
  converted `[embed]` (YouTube link).
- 99% of content is attributed to a shared `olli` account rather than the real named
  author (Olaf Taubert per Impressum) — not mechanically fixable from the export.
- **The supplied Datenschutzerklärung text describes Google Analytics, Usercentrics
  consent management, and Facebook/Twitter social widgets** — none of which this site
  actually runs (per the "no consent banner, by design" convention above, those scripts
  were deliberately dropped, not ported). The text was supplied verbatim and published
  as-is at `/datenschutz/`; worth a legal/compliance pass to trim the sections describing
  tools that aren't in use, since a privacy policy is supposed to reflect actual
  processing, not a superset of it.

## Real content vs. WordPress-sourced content

- `/impressum/` and `/datenschutz/` are **real, commissioned legal text** (Funktion5 GmbH),
  set directly as `Page` rows — not derived from the WXR export. `ImportWordPress::importPages()`
  explicitly skips the `impressum` slug (see its comment, same pattern as `weihnachtsgeschenke`)
  so a future re-import can never clobber this with the old eRecht24 placeholder. There is
  no `datenschutz` page in the WXR at all; edit both only via Filament or directly in the
  database going forward, never by touching the importer.
- `posts.featured_image` (added after the initial migration) is populated by resolving each
  post's `_thumbnail_id` WXR postmeta against the attachment map — see `downloadPostImage()`
  in `ImportWordPress.php`, mirroring `downloadProductImage()`'s pattern. Not every post has
  one; templates must handle a null `featured_image` gracefully (they do).
