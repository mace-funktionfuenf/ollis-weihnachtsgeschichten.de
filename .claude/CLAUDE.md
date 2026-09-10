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

- Language / framework: PHP 8.4, Laravel 13, Filament 3.3 (admin panel at `/admin`), SQLite
  locally / MySQL in production.
- Install: `composer install`
- Local server: `php artisan serve` (does **not** exercise `public/.htaccess` — the
  static-cache rewrite only applies under real Apache)
- Test: `vendor/bin/phpunit` (or `php artisan test`)
- Re-import from the WXR export: `php -d memory_limit=512M artisan import:wordpress`
  (idempotent — safe to re-run; upserts by `wp_post_id`, skips already-downloaded images)
- Regenerate the static export by hand: `php artisan export:static` (also runs
  automatically on every Filament save via `App\Observers\StaticExportObserver`)
- One-off content-fix commands (see README §2.7 for how to run these without SSH in
  production): `content:sync-legal`, `content:sync-author-profile`,
  `content:cleanup-legacy`, `content:fill-meta-descriptions`, `images:optimize`,
  `admin:create`, `content:seed-advent-calendar`. **Always follow any of these with
  `export:static`** — see the Gotchas section below, this bit us for real once already.

## Structure

- `app/Console/Commands/ImportWordPress.php` — the WXR→Eloquent import, including all
  shortcode-resolution judgment calls (see its doc comments for the reasoning per
  shortcode: `[produkte]`, `[ASA]`, `[wpsleep]`, `[caption]`, `[mapsmarker]`, `[embed]`,
  `[erecht24]`, `[borlabs-cookie]`).
- `app/Services/StaticSiteExporter.php` — renders every route via its Controller (plain
  method call, not an HTTP round-trip) and writes the HTML to `public/cache/`. Categories
  with a `parent_id` export under `/{parent-slug}/{slug}/`, root categories export flatly
  at `/{slug}/` — exporting every category flatly regardless of parent used to create an
  unroutable duplicate for each child category (fixed 2026-09-03).
- `routes/web.php` — legacy URL shapes are preserved deliberately: posts/pages/flat
  categories share bare `/{slug}/`, products live under `/produkt/`, product-taxonomy
  archives under `/fuer/`, `/weihnachtsgeschenke/`, `/weihnachtsgeschichten/` (the last one
  is *also* a real post category root — both are genuine, see the route comments).
- `app/Models/` — one model per WordPress post type/taxonomy actually found in the
  export (`Post`, `Page`, `Product`, `Shop`, `Category`, `Tag`, `ProductAudience`,
  `GiftCategory`, `MediaType`) plus `Redirect` for legacy-URL 301s. `Product.available`
  gates whether a product shows on any public listing (controllers constrain the eager
  load to `available = true`; Filament itself sees everything, unconstrained). `AdventDoor`
  (day 1-24, title, story_html) is the one model that isn't WordPress-sourced at all —
  see the Advent calendar entry below.
- **`/adventskalendergeschichten/`** (added 2026-09-10) — an Advent calendar: 24 doors,
  one new story each, that unlock day-by-day through December. Gating is **entirely
  client-side JS** (`resources/views/pages/advent-calendar.blade.php` compares the
  visitor's own `Date()` against each door's day) — there's no other way to do "unlock on
  a date" on a statically-exported site, since every page is pre-rendered once and served
  by Apache with no per-request server logic at all. All 24 stories are baked into the
  static HTML regardless of date (just visually/interactively locked), so this is
  obscurity, not real access control — fine for a fun content gimmick, not for anything
  sensitive. Two ways to see what's behind a door without waiting for its date: read the
  raw text in Filament (`/admin/advent-doors`), or open the real page with `?preview` in
  the URL, which unlocks every door client-side so it can be seen rendered and clicked
  exactly as visitors will see it. `AdventDoorResource` (Filament) manages the 24 door rows; placeholder text
  was seeded via `content:seed-advent-calendar` (idempotent — only fills missing days,
  real text goes in per-door via Filament before December). **This URL used to belong to
  a root `Category`** (a 3-post "Adventskalendergeschichte" archive: 2006/2007/2014) -
  deliberately replaced, not merged, per a 2026-09-10 decision. The category row and its
  post associations are untouched in the DB (the 3 posts stay reachable at their own
  slugs), but `StaticSiteExporter` explicitly skips exporting that one category by slug
  so it can never silently reclaim the URL depending on loop order - see the comment
  there before changing category-export order.
- `app/Support/ContentHtml.php` — post-processes any rendered body HTML (post/page/
  product/shop) at render time: external links get `target="_blank"` + `rel="noopener
  noreferrer"`, and heading levels are shifted so the shallowest one used becomes `<h2>`
  (a lot of imported WordPress content jumps straight to `<h3>`+ with no `<h2>`, breaking
  the page's semantic outline). Covers old imported content and anything written fresh in
  Filament with one rule.
- **No imported post/page has any `<p>` tags at all.** WordPress's classic editor stores
  one paragraph per raw line and only wraps them in `<p>` at render time (`wpautop()`);
  this rebuild never replicates that, so every imported body is one long unbroken text
  node. Usually harmless for a short single-narrative story, but genuinely broke
  `adventskalendergeschichte-2014` (24 images + day-segments crammed into one wall of
  text) - fixed 2026-09-10 via the one-off `content:format-advent-2014-story` (restores
  real `<p>` tags, unwraps each day's image from a dead "view full size" link - no
  lightbox script exists on this site). If another imported post ever turns out to have
  the same problem, that command is the template to copy, not a place to add more slugs
  to. Also added CSS support for `.amazonbutton` (`app.blade.php`, aliased onto the
  existing `.btn` rule) - a leftover WordPress class on affiliate text-links in ~33 posts
  that had no styling at all before.
- `app/Support/ImageOptimizer.php` — resizes to a 1200px-wide cap, recompresses, and
  converts non-transparent PNGs to JPEG, using GD (no new dependency). Wired into both
  `ImportWordPress::downloadTo()` (WXR-imported images) and every Filament `FileUpload`
  field (`saveUploadedFileUsing()` on Post's `featured_image` and Product's `image_path`)
  — the cap applies no matter how an image enters the system, not just imported ones.
  `app/Console/Commands/OptimizeImages.php` (`images:optimize`) is the one-off backfill
  for images that predate this.
- `resources/views/components/layouts/app.blade.php` — the only layout; plain
  hand-written CSS, no framework/CDN (GDPR self-hosting) except the CCM19 consent-manager
  script (see Conventions below). Also builds the canonical `<link>` tag (added
  2026-09-10) from a `:canonical` prop every page view passes in — built off
  `config('app.url')`, not `url()`/`request()`, since the static export calls each
  Controller directly rather than through an HTTP request (see
  `StaticSiteExporter`), so there's no real request to derive a host from.
  `Category` gained a `url()` method (mirroring the parent/flat routing split
  `StaticSiteExporter` already used) so its view could pass one too.

## Conventions

- `wp_post_id` columns are **nullable** — only WXR-imported rows have one; content
  created fresh in Filament doesn't need it.
- Slugs are never re-derived from titles on imported content (`wp:post_name` verbatim).
- **"Olli" is Olaf Taubert's pen name for the stories, not a data-quality issue.** 99% of
  imported content is attributed to a shared `olli` byline rather than his real name — that's
  intentional (per Impressum, Olaf Taubert is the real, named author; "Olli" is how he signs
  the stories themselves). The `/ueber-den-autor/` page and the "von {author}" link on every
  post exist so readers can find the real person behind the pen name, not to fix a mismatch.
- **Consent management: CCM19** (`cloud.ccm19.de`, provider papoo software & media GmbH),
  loaded sitewide in `app.blade.php`'s `<head>`. This reverses the project's original
  "no consent banner, by design" decision — the Twitter/Facebook/Google Analytics embeds
  from the legacy site are still genuinely dropped (not ported), but a real CMP is now in
  place regardless. It is the one script on this site that isn't self-hosted (see the
  GDPR self-hosting default) — that's an accepted, deliberate exception, not an oversight:
  a hosted CMP inherently can't be self-hosted the way a font or icon set can. Its own
  cookie (`langSet`) will show up in third-party-cookie audits; that's expected of any
  hosted CMP and isn't a compliance defect.

## Gotchas

- **Fixed 2026-09-10: running the test suite used to corrupt the real static cache.**
  Every Feature test runs against an isolated in-memory sqlite DB (`phpunit.xml`), but
  `StaticExportObserver::saved()`/`deleted()` fired on every test fixture save regardless
  and wrote straight to the real `public/cache/` on disk — so a local `vendor/bin/phpunit`
  run would silently overwrite real content with whatever thin test fixtures existed in
  that test's DB (discovered when the Advent calendar page's export came out looking
  empty right after a test run). `StaticExportObserver` now skips exporting when
  `app()->runningUnitTests()` is true. Not a production risk (README's deploy script
  never runs the test suite), but locally: if a `public/cache/` page ever looks wrong
  right after running tests, that's `export:static` needing a re-run, not a real bug in
  the page.
- **A Scheduled Task does not regenerate the static cache.** `export:static` only runs
  automatically (a) on a Filament save, or (b) as a step in the Git deployment-actions
  script (README §2.1) — a one-off content-fixing command run via Plesk Scheduled Tasks
  (README §2.7) is neither. Run `export:static` as a Scheduled Task immediately after any
  command that changes stored content, file paths, or filenames (`content:cleanup-legacy`,
  `images:optimize`, etc.) — skipping this caused a real incident (images "disappeared"
  after `images:optimize` renamed some files; the cached HTML still pointed at the old
  names/paths until `export:static` ran).
- The live site's WAF resets connections from Guzzle's default User-Agent — any future
  outbound HTTP call to the old domain needs a browser-like `User-Agent` header (see
  `downloadTo()` in `ImportWordPress.php`) or it'll fail with `cURL error 56`.
- **Don't re-run `import:wordpress` just to inspect the export.** It's a full
  `updateOrCreate` on every post/page from the raw WXR content — it'll blow away any
  hand-fixed `body_html`/`meta_description` (the geschenkideen rewrite, the
  meta-description backfill, one-off content edits, etc.). To check something in the WXR
  file, parse it directly (`simplexml_load_file`) instead.
- Blade anonymous components must live under `resources/views/components/`, not
  `resources/views/partials/` — `<x-product-card>` resolves to
  `components/product-card.blade.php`.
- `Filament\Http\Middleware\Authenticate` 403s any user that doesn't implement
  `FilamentUser`, *unless* `APP_ENV=local` — `App\Models\User` implements it explicitly
  (`canAccessPanel()` always `true`, single-editor site) so this doesn't silently break
  outside local/testing envs.
- `public/cache/` is a fully disposable build artifact (gitignored) — never hand-edit
  files in it, edit content in Filament and let the observer/export command regenerate it.
- Plesk's own "Password-protected Directories" feature manages HTTP Basic Auth at the
  Apache **vhost-config level**, not via `.htaccess` — don't add `AuthType`/`AuthUserFile`
  directives to the git-tracked `public/.htaccess` to replicate this; that file gets
  redeployed on every push and will conflict with (or duplicate) what Plesk already
  manages independently. Learned the hard way: doing this caused a live 500 error.

## Outstanding

- `/geschenkideen/`'s intro paragraph got its one-off "short trends text" refresh (see git
  history). Still open: the client's "optimal: jede Woche aktualisieren lassen" — genuine
  weekly auto-refresh needs an LLM API call on a schedule, a new dependency + architecture
  decision. Ask before building it.

## Real content vs. WordPress-sourced content

- `/impressum/`, `/datenschutz/`, and `/ueber-den-autor/` are **real, hand-authored
  content** (the first two commissioned legal text from Funktion5 GmbH, the third Olaf
  Taubert's bio), set directly as `Page` rows — not derived from the WXR export.
  `ImportWordPress::importPages()` explicitly skips the `impressum` slug (see its comment,
  same pattern as `weihnachtsgeschenke`) so a future re-import can never clobber this with
  the old eRecht24 placeholder. There is no `datenschutz` or `ueber-den-autor` page in the
  WXR at all. Each has a matching one-off sync command (`content:sync-legal`,
  `content:sync-author-profile`) that upserts it by slug — idempotent, safe to re-run
  whenever the text changes, and the only way this content reaches a fresh environment
  (production included) since it isn't part of the WXR import. Edit the text in the
  command file itself, or directly in Filament/the database — never by touching the
  importer.
- `posts.featured_image` (added after the initial migration) is populated by resolving each
  post's `_thumbnail_id` WXR postmeta against the attachment map — see `downloadPostImage()`
  in `ImportWordPress.php`, mirroring `downloadProductImage()`'s pattern. Not every post has
  one; templates must handle a null `featured_image` gracefully (they do).
