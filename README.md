# ollis-weihnachtsgeschichten.de

Laravel + Filament rebuild of a legacy WordPress site (German-language humorous
Christmas stories since 2000, plus an Amazon-affiliate gift/product catalogue).

Filament (`/admin`) is the authoring backend. Visitors never hit a Blade render on a
live request — every page is pre-rendered to a static HTML file under
`public/cache/**/index.html` by `App\Services\StaticSiteExporter`, and
`public/.htaccess` serves that file directly when it exists, falling through to the
normal Laravel front controller only on a cache miss (admin, Livewire, assets, or a
route with no cached file). Saving anything in Filament re-triggers the export
automatically via `App\Observers\StaticExportObserver`. Keep this in mind throughout:
**the static cache only rewrites correctly under real Apache with `mod_rewrite`** —
`php artisan serve` and most other dev servers don't honour `.htaccess`, so locally
you'll always see the live Blade render, not the cached file.

- Stack: PHP 8.3+, Laravel 13, Filament 3.3, SQLite locally / MySQL in production.
- Node: Vite 8 + Tailwind 4 for the small amount of hand-written CSS/JS.

---

## 1. Local setup

### 1.1 Requirements

- PHP **8.3+** with the extensions Laravel needs (`pdo_sqlite`, `mbstring`, `openssl`,
  `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`).
- Composer 2.x.
- Node.js 20+ and npm (for the Vite build — only needed for the small CSS/JS bundle,
  not for content).
- Apache with `mod_rewrite`, **if** you want to test the static-cache behaviour
  itself (see the callout above). Not required for day-to-day content work.

### 1.2 Clone and install

```bash
git clone <repo-url> ollis-weihnachtsgeschichten.de
cd ollis-weihnachtsgeschichten.de

composer install
npm install
```

### 1.3 Environment

```bash
cp .env.example .env
php artisan key:generate
```

The defaults in `.env.example` already point at SQLite (`DB_CONNECTION=sqlite`), which
is what local development uses. Create the database file (it's gitignored):

```bash
touch database/database.sqlite
```

Leave `APP_ENV=local` — this matters beyond convention: `App\Models\User::canAccessPanel()`
grants Filament access to every row in `users` (single-editor site, no roles), but
Filament's own `Authenticate` middleware would 403 a user that didn't implement
`FilamentUser` in any environment *other than* `local`. Only change `APP_ENV` once you're
sure the model's `canAccessPanel()` logic is what you want in that environment.

### 1.4 Database and content

```bash
php artisan migrate
```

To populate the database from the WordPress export instead of starting empty, run the
importer (idempotent — safe to re-run, upserts on `wp_post_id`, skips already-downloaded
images):

```bash
php -d memory_limit=512M artisan import:wordpress
```

Or seed a single throwaway user without any content:

```bash
php artisan db:seed
```

### 1.5 Build assets and run

```bash
npm run build     # one-off production build of resources/css and resources/js
# or
npm run dev       # Vite dev server with HMR, while iterating on styles

php artisan serve
```

Visit `http://127.0.0.1:8000`. Remember this bypasses `public/.htaccess`, so you're
always seeing the live Blade route, never `public/cache/`.

To generate the static export by hand (it also runs automatically on every Filament
save):

```bash
php artisan export:static
```

### 1.6 Admin panel

`http://127.0.0.1:8000/admin` — log in with whatever user you created in step 1.7
below (there is no public registration; this is a single-editor CMS).

### 1.7 Create your first local user

Filament ships an interactive command for this:

```bash
php artisan make:filament-user
```

It prompts for name, email, and password. Alternatively use the seeded
`test@example.com` user from `database/seeders/DatabaseSeeder.php` (only if you ran
`db:seed`) or Tinker:

```bash
php artisan tinker --execute="\App\Models\User::create(['name' => 'Your Name', 'email' => 'you@example.com', 'password' => 'a-strong-password']);"
```

`password` is cast to `hashed` on the model (`app/Models/User.php`), so a plain string
here is bcrypt-hashed automatically — never hash it yourself before this call.

---

## 2. Production deployment (Plesk on Wint.global)

The live site runs on **Plesk**, hosted by **Wint.global**, currently on the subdomain
`static.ollis-weihnachtsgeschichten.de` (a soft-launch domain ahead of cutting the real
apex domain over). **There is no SSH/terminal access on this plan** — everything below
happens through Plesk's web panel. This section documents what's actually configured
and the real problems hit setting it up, not a generic "some shared host" guide — if
you ever move to a different host, §2.7's underlying idea (use whatever the control
panel offers for one-off command execution — cron, a scheduled-task feature, anything
that isn't a shell) still generalizes.

### 2.1 Git-based deployment (the actual mechanism)

Websites & Domains → the subdomain → **Git**:

- Repository: `https://github.com/mace-funktionfuenf/ollis-weihnachtsgeschichten.de.git`
  — public, so no credentials/deploy keys needed — branch `main`.
- Repository directory: the default (`httpdocs`) is fine; see §2.2 for why.
- **"Additional deployment actions"** is the field that stands in for SSH here: a
  shell script Plesk runs after every pull. Current script:

  ```bash
  /opt/plesk/php/8.4/bin/php artisan migrate --force
  [ -L public/storage ] || /opt/plesk/php/8.4/bin/php artisan storage:link
  /opt/plesk/php/8.4/bin/php artisan export:static
  ```

  The full PHP path (instead of bare `php`) is deliberate — see the version gotcha in
  §2.3. `composer install` is deliberately *not* in this script; run it via Plesk's
  separate **Composer** panel for the domain instead (Websites & Domains → the
  subdomain → Composer) whenever `composer.lock` changes — a simpler, already-proven
  path that doesn't risk `composer` not resolving inside the deployment shell.
- Trigger a deploy either manually ("Pull updates" in the Git tool) or automatically —
  the Git tool shows a webhook URL; add it under the GitHub repo's Settings → Webhooks
  and every `git push` to `main` triggers a pull plus the script above with no manual
  step at all.

### 2.2 Document root

Hosting Settings for the subdomain → **Document root** → `httpdocs/public`. Plesk lets
the document root point at a subfolder of wherever the Git tool clones the repo, so
`app/`, `vendor/`, `.env`, etc. physically sit inside `httpdocs` but are never
web-reachable — no need for the split-directory trick a plain FTP host without this
setting would require.

### 2.3 PHP version

PHP Settings for the subdomain → **8.4**. (`composer.json` says `^8.3`, but the
current `composer.lock` was resolved against 8.4 and its generated
`platform_check.php` enforces ≥8.4.1 specifically — match that, not the composer.json
floor.)

**The gotcha that actually bit us:** this PHP Settings page only governs PHP-FPM for
real HTTP requests — it does *not* change what a bare `php` resolves to in a Scheduled
Task or the Git deployment-actions shell. We hit this directly: Composer built
`vendor/` against 8.4 while a deployment action ran `artisan` under system PHP 8.2.32,
producing a `platform_check.php` fatal error on every command. Always use the full CLI
binary path instead of bare `php` in both those contexts. Find it via the
`include_path` shown on the PHP Settings page (ours listed
`/opt/plesk/php/8.4/share/pear`, confirming the binary lives at
`/opt/plesk/php/8.4/bin/php`).

### 2.4 Database

Websites & Domains → the subdomain → **Databases** → create a database and a user.

**A second gotcha:** on this host the database runs on separate infrastructure from
the webserver — `DB_HOST` is a real IP, not `localhost`. That's normal for
Plesk-managed clustered hosting, not a misconfiguration. If you get
`SQLSTATE[HY000] [1045] Access denied` despite a correct password, MySQL's error text
doesn't distinguish "wrong password" from "this user isn't authorized from this host"
— check that the database user is actually attached to that specific database (Plesk →
the database → **Database Users** tab, not just present on the account generally) and
that its allowed connection host covers wherever Plesk's PHP processes actually
connect from (the error itself names it, e.g. `user@ha01s015.org-dns.com` — not
`localhost`).

### 2.5 `.env`

Created once via File Manager (into `httpdocs`) — it's gitignored, so it survives
every future pull untouched.

```env
APP_NAME="Ollis Weihnachtsgeschichten"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://static.ollis-weihnachtsgeschichten.de
APP_KEY=                    # generate locally: php artisan key:generate --show
APP_LOCALE=de
APP_FALLBACK_LOCALE=de

DB_CONNECTION=mysql
DB_HOST=<the IP Plesk shows for this database>
DB_DATABASE=<from §2.4>
DB_USERNAME=<from §2.4>
DB_PASSWORD=<from §2.4>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack
LOG_LEVEL=error
```

`App\Support\ContentHtml::externalLinksInNewTab()` (see §4) reads `APP_URL`'s host at
runtime to decide which links count as "internal" — get this right here and that logic
needs no separate update when the domain changes later (e.g. cutting over to the real
apex domain).

### 2.6 SSL

SSL/TLS Certificates, on the *subdomain's own* management page → Let's Encrypt →
Install. A certificate already issued for the parent domain does not automatically
cover a subdomain unless it's a wildcard — issue one specifically for
`static.ollis-weihnachtsgeschichten.de` (or add it as a SAN).

### 2.7 One-off commands without SSH: Scheduled Tasks

The deployment-actions script in §2.1 covers everything that needs to run on *every*
deploy. A few things only need to run **once** and don't belong in that repeating
script — use Scheduled Tasks instead: Websites & Domains → the subdomain →
**Scheduled Tasks** → Add Task → task type "Run a PHP script" → PHP version 8.4 →
**Script**: click **Browse** and select `artisan` from the file tree (don't type a path,
and don't put the `php` binary path here — that field is the script to run, Plesk
supplies the interpreter itself via the PHP version dropdown) → **Arguments**: the
command. Most Plesk versions show a **"Run Now"** action once the task is saved, so it
doesn't have to wait for its schedule. Check the run log for a clean exit, then
**delete the task** — it was a one-shot, not a recurring job.

**⚠️ Always run `export:static` as a follow-up Scheduled Task after any command below
that changes stored content, filenames, or file paths.** None of these are deploy
events, so none of them trigger §2.1's automatic re-export — the live pages are static
HTML that will keep serving whatever it already had (including references to files
that no longer exist under their old name) until `export:static` runs again. This is
not a hypothetical: skipping it once made every converted image "disappear" site-wide,
even though the files themselves were fine.

Commands that have needed this route so far:

1. **`import:wordpress`** — populates Posts/Pages/Products/Categories/Redirects from
   the WXR export (`ollisweihnachtsgeschichten.WordPress.2026-08-19.xml`, committed to
   the repo, so it's already present after the Git pull with no separate upload
   needed). Idempotent, but too expensive to run on every deploy, which is why it's
   not in §2.1's script — re-run it by hand any time importer code changes in a way
   that should backfill already-imported rows (e.g. adding `featured_image` support
   required re-running this once against posts imported before that column existed).
2. **Creating the first admin user** — see §3.3, including a third gotcha this
   uncovered with the Arguments field itself.
3. **`content:sync-legal`** — upserts the Impressum/Datenschutz `Page` rows with the
   current legal text. This content lives only in the command file (see §4), never in
   the WXR export, so this is the only way it reaches a fresh environment. Idempotent;
   re-run whenever the text in the command changes.
4. **`content:sync-author-profile`** — same idea as above, for the `/ueber-den-autor/`
   page (Olaf Taubert's bio).
5. **`content:cleanup-legacy`** — one-time batch fix from the 2026-09 pre-launch
   review: renames the `storage/app/public/wordpress/` folder to `media/` (and rewrites
   the ~34 posts/pages that reference it inline), strips an old insecure `http://`
   Amazon iframe out of one post, and refreshes the outdated 2015 intro text on
   `/geschenkideen/`. Idempotent — every step checks whether it's already done first.
6. **`content:fill-meta-descriptions`** — backfills a CTA-bearing `meta_description`
   for any post/product/page that doesn't have one yet, derived from existing content.
   Only ever fills nulls, safe to re-run after adding new content.
7. **`images:optimize`** — one-off backfill resizing/recompressing every already-stored
   post/product image (new uploads are capped automatically going forward, see §4's
   `ImageOptimizer` entry — this command is only for images that predate that).

### 2.8 The static cache after a deploy

`public/cache/` is gitignored — a `git push` deploys new code and templates but never
touches the old pre-rendered HTML already sitting on the server from a previous
`export:static` run. Since `export:static` is now in the repeating deployment script
(§2.1), a normal deploy already regenerates it; if a page ever shows a mix of old and
new content right after a deploy, that script either didn't run or failed partway —
check its log in the Git tool.

**Known gotcha, not yet fixed:** `public/.htaccess`'s trailing-slash redirect (strips
a trailing `/` when the requested path isn't a real directory) runs *after* the
static-cache check but *before* the front-controller fallback. A URL requested with a
trailing slash that currently has no cache file gets redirected to the slash-less
version — and that second, slash-less request can never match the cache rule (its path
concatenation needs the trailing slash to form `cache/<slug>/index.html`), so it always
falls through to a live Laravel render instead of ever getting a cached copy for that
exact URL shape. Content still renders correctly either way, just without the
static-cache performance benefit for that one URL until it's addressed.

### 2.9 HTTP Basic Auth on the staging subdomain

While the site is on the temporary `static.ollis-weihnachtsgeschichten.de` subdomain
ahead of the real domain cutover, the whole thing sits behind an HTTP Basic Auth
prompt so it isn't publicly indexed/browsable mid-review.

**This is set up entirely through Plesk's GUI, not through this repo:** Websites &
Domains → the subdomain → **Password-protected Directories** → protect the `public`
directory (the docroot) → add a username/password. Plesk manages this at the
**Apache vhost-config level**, not by writing into `.htaccess` — which matters,
because it means:

- It's completely independent of `git push` / the deployment script. It won't be
  wiped by a deploy, and it doesn't need anything added to `public/.htaccess`.
- **Don't try to replicate it by hand-adding `AuthType`/`AuthUserFile` directives to
  the git-tracked `public/.htaccess`.** That file redeploys on every push, so a
  manually-added block there will drift out of sync with (or outright conflict with)
  what Plesk is managing independently — this was tried once and produced a live 500
  error (`AuthUserFile` pointing at a path that didn't match what Plesk had actually
  set up). If you ever need to inspect exactly what Plesk configured, the fastest way
  is to open the *live* `.htaccess` in Plesk's File Manager and read it directly,
  rather than guessing.

Remove the protected-directory entry in Plesk once the real domain goes live and the
site is meant to be public.

---

## 3. Managing users (Filament / admin accounts)

There is no `UserResource` in `app/Filament/Resources/` — by design, this is a
single-editor CMS, so there's no in-panel screen for admins to manage other admins.
Every row in `users` is trusted with full panel access
(`App\Models\User::canAccessPanel()` always returns `true`). Adding or removing a user
is always a CLI or direct-database operation, on both dev and prod.

### 3.1 Add a user — local dev

```bash
php artisan make:filament-user
```

Interactive prompts for name, email, password. Or non-interactively via Tinker:

```bash
php artisan tinker --execute="\App\Models\User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'a-strong-password']);"
```

### 3.2 Remove a user — local dev

```bash
php artisan tinker --execute="\App\Models\User::where('email', 'jane@example.com')->delete();"
```

Don't delete the only remaining user — you'd lock yourself out of `/admin` with no
in-panel way to create another one.

### 3.3 Add a user — production (no SSH)

**Preferred: `app/Console/Commands/CreateAdminUser.php`** (`artisan admin:create`),
built specifically for this. A raw Tinker one-liner run through Plesk's Scheduled
Tasks "Arguments" field turned out to be fragile — a
`tinker --execute="...(['name' => 'X', 'password' => 'Y'])..."` command silently lost
its `password` value in transit (the resulting `INSERT` was missing that column
entirely, no error raised). `admin:create` takes plain, quote-free `--flag=value`
arguments instead, so there's nothing left for a panel's argument field to mangle:

Run once via Scheduled Tasks (§2.7):

```
Arguments: admin:create --name=Jane --email=jane@example.com --password=a-strong-password
```

(Keep `--name` to a single word if you're not sure the field preserves spaces —
`updateOrCreate()` runs underneath, keyed on email, so it's also safe to reuse later
purely to reset a password.)

**Fallback — direct insert via phpMyAdmin** (works on any host, no cron/scheduled-task
feature required):

1. Locally, generate a bcrypt hash for the intended password — never insert a plain
   password into the `password` column:

   ```bash
   php artisan tinker --execute="echo Hash::make('a-strong-password');"
   ```

2. In phpMyAdmin, insert a row into `users` with:
   - `name`: the person's display name
   - `email`: their login email (must be unique)
   - `password`: the bcrypt hash from step 1 (starts with `$2y$`)
   - `email_verified_at`: leave `NULL`
   - `remember_token`: leave `NULL`
   - `created_at` / `updated_at`: current timestamp

### 3.4 Remove a user — production (no SSH)

**Option A — Tinker via Scheduled Tasks:**

```
Arguments: tinker --execute="\App\Models\User::where('email','jane@example.com')->delete();"
```

This one-liner has no `=>` array syntax and only one pair of quotes, so it's much less
likely to hit the same mangling as the create case in §3.3 — but if it does, delete the
row directly instead (Option B).

**Option B — phpMyAdmin:** open the `users` table, find the row by `email`, delete it
directly.

As in local dev: always confirm at least one other working login exists before
deleting a user, and delete the Scheduled Task immediately after either operation —
it's a one-shot task, not something that should keep running.

---

## 4. Reference

- `app/Console/Commands/ImportWordPress.php` — the WXR→Eloquent import and all
  shortcode-resolution decisions. `impressum` is explicitly skipped on re-import (real,
  hand-authored content now lives directly in the `pages` table, not derived from the
  export) — see its inline comment before touching that logic.
- `app/Console/Commands/CreateAdminUser.php` — non-interactive admin-user creation for
  hosts without SSH; see §3.3 for why this exists instead of `make:filament-user`.
- `app/Console/Commands/SyncLegalPages.php` (`content:sync-legal`) and
  `SyncAuthorProfile.php` (`content:sync-author-profile`) — upsert the Impressum/
  Datenschutz and "Über den Autor" `Page` rows by slug. This is real content that only
  exists in these command files, not the WXR export — see §2.7 point 3/4.
- `app/Console/Commands/CleanupLegacyContent.php` (`content:cleanup-legacy`) and
  `FillMetaDescriptions.php` (`content:fill-meta-descriptions`) — one-off content
  fixes from the 2026-09 pre-launch review; see §2.7 points 5/6 for what each does.
- `app/Support/ContentHtml.php` — renders body HTML (post/page/product/shop) with
  external links opening in a new tab and heading levels normalized so they never skip
  below `<h2>`, applied at render time so it covers both imported WordPress content and
  anything written fresh in the RichEditor with one rule. Internal-vs-external is
  decided from `APP_URL`'s host at runtime — see §2.5.
- `app/Support/ImageOptimizer.php` (`storeAndOptimize()` for Filament uploads,
  `optimize()` for the importer) and `app/Console/Commands/OptimizeImages.php`
  (`images:optimize`, the one-off backfill) — see §2.7 point 7. Resizes to a 1200px cap
  and recompresses using GD; no new Composer dependency.
- `app/Services/StaticSiteExporter.php` — renders every route to `public/cache/`. Root
  categories export flatly (`/{slug}/`), categories with a parent export nested
  (`/{parent-slug}/{slug}/`) — matching `routes/web.php` exactly matters here, since
  exporting a child category flatly once created an unroutable duplicate URL.
- `app/Observers/StaticExportObserver.php` — re-runs the exporter on every Filament save.
- `public/.htaccess` — the rewrite rule that serves `public/cache/` ahead of Laravel;
  see §2.8 for a known gap in how it interacts with trailing slashes. **Not** where HTTP
  Basic Auth lives — see §2.9.
- `routes/web.php` — legacy WordPress URL shapes, preserved deliberately; see the
  inline comments for why `/weihnachtsgeschichten` and a couple of other paths do
  double duty.
- `.claude/CLAUDE.md` — fuller project conventions and outstanding issues.
