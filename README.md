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

## 2. Deploying to a webserver without SSH access

This assumes typical shared hosting: FTP/SFTP for files, a control panel (cPanel/Plesk/
similar) with a **Cron Jobs** feature, phpMyAdmin for the database, and a MySQL
database — but **no interactive shell**. Cron jobs matter a lot here: on almost every
host that disables SSH, the cron job runner still exists and can execute an arbitrary
CLI command (including `php artisan ...`) without ever giving you a shell. That's the
core trick used throughout this section for anything you'd normally do with `artisan`
on the server.

### 2.1 Build the deployment package locally

Never run `composer install` or `npm install` on the server — you don't have a shell to
run them in anyway. Build everything locally and upload the result:

```bash
composer install --no-dev --optimize-autoloader
npm run build
```

Files/folders to upload via FTP (everything except what's below):

- **Exclude**: `.git/`, `node_modules/`, `tests/`, `.env` (see 2.4 — create it directly
  on the server instead of uploading your local one), `database/database.sqlite`,
  `public/cache/` (regenerated on the server, see 2.6), `public/build/` is **not**
  excluded — that's the compiled Vite output you just built and it must be uploaded.
- **Include everything else**, notably `vendor/` (since you can't run Composer on the
  server) and `public/build/`.

### 2.2 Where the document root points

Laravel's front controller lives at `public/index.php`, and the app's `.htaccess`
(`public/.htaccess`) relies on `%{DOCUMENT_ROOT}` pointing at that same `public/`
folder. Check which of these two situations your hosting gives you:

**Scenario A — you can set the document root to a subfolder** (common for addon
domains/subdomains in cPanel, or with a dedicated vhost). This is the easy case:

1. Upload the whole project to any folder outside the web root, e.g.
   `/home/youruser/ollis-weihnachtsgeschichten/`.
2. In the control panel, point the domain's document root at
   `/home/youruser/ollis-weihnachtsgeschichten/public`.
3. Nothing else to change — `public/.htaccess` and `public/index.php` work as-is.

**Scenario B — the document root is fixed to `public_html/`** (typical on basic
shared-hosting plans with no per-domain document-root setting). You have to split the
app from the web root:

1. Upload the project *except* the `public/` folder's contents to a directory **above**
   the web root, e.g. `/home/youruser/app/` (a sibling of `public_html/`, not inside
   it — this keeps `app/`, `.env`, `vendor/`, etc. unreachable from the web).
2. Upload the *contents* of `public/` (not the folder itself) directly into
   `public_html/` — so `public_html/index.php`, `public_html/.htaccess`,
   `public_html/build/`, etc.
3. Edit `public_html/index.php` — it now needs to reach one level *up and over* into
   `app/` instead of one level up into a sibling `bootstrap/`/`vendor/`. Change:

   ```php
   if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```

   to:

   ```php
   if (file_exists($maintenance = __DIR__.'/../app/storage/framework/maintenance.php')) {
   require __DIR__.'/../app/vendor/autoload.php';
   $app = require_once __DIR__.'/../app/bootstrap/app.php';
   ```

   (adjust `../app/` to whatever your actual sibling folder is named).
4. `public_html/.htaccess` needs no change — `%{DOCUMENT_ROOT}` now correctly resolves
   to `public_html/`, which is exactly what the `RewriteCond ... /cache%{REQUEST_URI}`
   rule expects.

Either way, confirm `mod_rewrite` is enabled (it is by default on virtually every
cPanel host) — without it the static-cache rewrite and Laravel's pretty URLs both break.

### 2.3 Database on the server

Use MySQL in production, not SQLite — create a database and a dedicated DB user via
the control panel's MySQL Databases tool, and note the host/name/user/password
(usually `localhost` and a prefixed name like `youruser_ollis`).

### 2.4 `.env` on the server

Don't upload your local `.env`. Create a fresh one directly in the server's file
manager (or FTP up a purpose-built production `.env`, never committed to git — it's in
`.gitignore`) with at least:

```env
APP_NAME="Ollis Weihnachtsgeschichten"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ollis-weihnachtsgeschichten.de
APP_KEY=                      # see below

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youruser_ollis
DB_USERNAME=youruser_ollis
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_LEVEL=error
```

`APP_ENV=production` matters for the same reason noted in 1.3, but the other way:
production is exactly the environment where you want Filament's normal
`FilamentUser`/auth checks to apply without any local-only shortcut, so leave it as
`production` and don't be tempted to set it to `local` on a live site.

Generate `APP_KEY` locally (it must be a real random key, not left blank) and paste it
in:

```bash
php artisan key:generate --show
```

### 2.5 Running `artisan` commands on the server without SSH

Use the control panel's **Cron Jobs** page to run a one-off command, even though
nothing here is actually meant to run on a schedule:

1. Add a cron job with the command, e.g. (path to your PHP CLI binary varies by host —
   check the control panel's PHP version selector, which usually shows the CLI path):

   ```bash
   /usr/local/bin/php /home/youruser/app/artisan migrate --force
   ```

2. Set it to run once, a minute or two in the future (most UIs let you pick an exact
   minute rather than only interval presets).
3. Let it fire, then check the result (either a "last run" log the panel shows, or by
   checking effects in phpMyAdmin — e.g. the `migrations` table now has rows).
4. **Delete the cron job** immediately afterwards — it was a one-shot, not a recurring
   task.

Use the same mechanism for any other one-off `artisan` command you'd normally run over
SSH — `migrate`, `migrate:rollback`, `optimize`, `config:cache`, `import:wordpress`,
`export:static`, or the Tinker commands used for user management in §3.2.

**If your host has no cron feature at all** (rare, but some ultra-basic plans), the
fallback is a temporary, secret-gated web route. Add something like this to
`routes/web.php`, deploy it, hit the URL once, then delete the route and redeploy:

```php
Route::get('/deploy-task-' . config('app.key'), function () {
    Artisan::call('migrate', ['--force' => true]);
    return Artisan::output();
});
```

Never leave a route like this in place — it's a temporary tool, not a feature. Delete
it and redeploy as the very next step after using it.

### 2.6 The `storage` symlink (file uploads)

Filament's `FileUpload` fields (e.g. the product image field in
`app/Filament/Resources/ProductResource.php`) write to the `public` disk
(`storage/app/public/...`), which is normally exposed at `public/storage` via a
symlink created by `php artisan storage:link`. Symlinks are frequently unavailable or
awkward over plain FTP. Two options, in order of preference:

- Run `php artisan storage:link` once via the cron-job method in §2.5 — this is the
  correct fix and works on any host where PHP's `symlink()` function isn't disabled
  (most aren't).
- If `symlink()` genuinely doesn't work on your host, set `FILESYSTEM_DISK=public` in
  root and instead create `public_html/storage` (Scenario B) / `public/storage`
  (Scenario A) as a **real folder** via FTP, matching the path Filament writes to. This
  is a workaround, not the default Laravel setup — only fall back to it if the symlink
  approach is confirmed broken on your specific host.

### 2.7 First deploy: full checklist

1. `composer install --no-dev --optimize-autoloader` and `npm run build` locally.
2. Upload files per the layout chosen in §2.2.
3. Create the MySQL database + user in the control panel.
4. Create `.env` on the server per §2.4, with a real `APP_KEY`.
5. Cron-run (§2.5): `artisan migrate --force`.
6. Cron-run: `artisan storage:link` (§2.6).
7. Cron-run: `artisan import:wordpress` — **only** if this is the initial data load
   from the WXR export; skip on subsequent deploys.
8. Cron-run: `artisan export:static` to populate `public/cache/` (subsequent content
   edits regenerate this automatically via the Filament save observer — see the intro).
9. Create your first admin user — see §3.3 below.
10. Visit the site and `/admin`, confirm both load, confirm a known URL is served from
    `public/cache/` (view source / check response headers for anything your host adds,
    or just confirm it still renders correctly with the DB briefly unreachable).

### 2.8 Redeploying updates

For routine code/content changes after the first deploy:

1. Build locally (`composer install --no-dev`, `npm run build` if assets changed).
2. Upload changed files via FTP (skip `.env`, skip `public/cache/` — Filament saves
   regenerate it, and a stale local copy would otherwise overwrite server content).
3. Cron-run `artisan migrate --force` only if this release includes new migrations.
4. If you changed anything Filament renders in bulk (a resource, a Blade view used by
   the exporter) rather than through the admin UI itself, cron-run `artisan
   export:static` to force a full re-render, since the observer only fires on actual
   Filament saves.

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

Two options; prefer the first.

**Option A — cron-triggered Tinker** (§2.5 explains the general cron-job technique):

Add a one-off cron job running:

```bash
/usr/local/bin/php /home/youruser/app/artisan tinker --execute="\App\Models\User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'a-strong-password']);"
```

Let it fire once, confirm the row exists (phpMyAdmin → `users` table, or just try
logging in at `/admin`), then delete the cron job.

**Option B — direct insert via phpMyAdmin** (if your host has no cron feature at all):

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

**Option A — cron-triggered Tinker:**

```bash
/usr/local/bin/php /home/youruser/app/artisan tinker --execute="\App\Models\User::where('email', 'jane@example.com')->delete();"
```

Run once via cron, confirm, then delete the cron job.

**Option B — phpMyAdmin:** open the `users` table, find the row by `email`, delete it
directly.

As in local dev: always confirm at least one other working login exists before
deleting a user, and delete/disable the cron job immediately after either operation —
it's a one-shot task, not something that should keep running.

---

## 4. Reference

- `app/Console/Commands/ImportWordPress.php` — the WXR→Eloquent import and all
  shortcode-resolution decisions.
- `app/Services/StaticSiteExporter.php` — renders every route to `public/cache/`.
- `app/Observers/StaticExportObserver.php` — re-runs the exporter on every Filament save.
- `public/.htaccess` — the rewrite rule that serves `public/cache/` ahead of Laravel.
- `routes/web.php` — legacy WordPress URL shapes, preserved deliberately; see the
  inline comments for why `/weihnachtsgeschichten` and a couple of other paths do
  double duty.
- `.claude/CLAUDE.md` — fuller project conventions and known outstanding issues
  (notably: the Impressum/Datenschutz pages have placeholder legal text only).
