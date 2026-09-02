<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;

class HomeController extends Controller
{
    public function show(): View|RedirectResponse
    {
        // Legacy "?p=123" permalinks carry a bare "/" path - the id can
        // only be resolved here, before the home route always matches it.
        if ($wpId = Request::query('p')) {
            $target = Post::where('wp_post_id', $wpId)->first()?->url()
                ?? Page::where('wp_post_id', $wpId)->first()?->url()
                ?? Product::where('wp_post_id', $wpId)->first()?->url();

            if ($target) {
                return redirect($target, 301);
            }
        }

        $latest = Post::where('status', 'publish')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return view('pages.home', [
            'intro' => Page::where('slug', 'startseite')->first(),
            'latestPost' => $latest->first(),
            'recentPosts' => $latest->slice(1),
        ]);
    }
}
