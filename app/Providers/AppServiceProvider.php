<?php

namespace App\Providers;

use App\Models\Category;
use Carbon\Carbon;
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
    }
}
