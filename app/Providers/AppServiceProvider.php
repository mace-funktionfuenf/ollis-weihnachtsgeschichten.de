<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\StaticSiteExporter;
use Carbon\Carbon;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel's app locale (config/app.php) doesn't automatically apply
        // to Carbon - translatedFormat() would otherwise keep rendering
        // English month names ("24. December") on this German-only site.
        Carbon::setLocale(config('app.locale'));

        // The main nav's "Ollis Weihnachtsgeschichten" dropdown lists every
        // yearly story - sourced from the category rather than hardcoded so
        // next year's story appears automatically, matching the legacy
        // site's menu without needing a manual edit here every December.
        View::composer('components.layouts.app', function ($view) {
            $view->with(
                'navStories',
                Category::where('slug', 'weihnachtsgeschichten')->first()
                    ?->posts()
                    ->where('status', 'publish')
                    ->orderByDesc('published_at')
                    ->get(['posts.id', 'posts.slug', 'posts.published_at'])
                    ?? collect()
            );
        });

        // Every one-off "content:*" fix command changes the database but
        // never touches public/cache/ itself - StaticExportObserver covers
        // Filament saves, but a content:* command run as a Plesk Scheduled
        // Task (README §2.7, no SSH on this host) doesn't reliably trigger
        // it, so the static export was repeatedly left stale until someone
        // remembered the separate "and now run export:static" step by hand.
        // Re-export unconditionally after every content:* command instead,
        // regardless of why the observer didn't fire - cheap for a ~125-page
        // site, and removes the human-memory step entirely rather than
        // patching each command (or relying on whoever writes the next one
        // to remember it too).
        Event::listen(function (CommandFinished $event) {
            if ($event->exitCode !== 0) {
                return;
            }

            if (! str_starts_with((string) $event->command, 'content:')) {
                return;
            }

            if (app()->runningUnitTests()) {
                return;
            }

            app(StaticSiteExporter::class)->exportAll();
        });
    }
}
