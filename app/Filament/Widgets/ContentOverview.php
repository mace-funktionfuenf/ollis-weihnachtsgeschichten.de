<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Beiträge', Post::where('status', 'publish')->count())
                ->description(Post::where('status', 'draft')->count().' im Entwurf')
                ->icon('heroicon-o-book-open')
                ->color('success'),
            Stat::make('Seiten', Page::count())
                ->icon('heroicon-o-document-text')
                ->color('gray'),
            Stat::make('Produkte', Product::count())
                ->description(Product::where('available', true)->count().' verfügbar')
                ->icon('heroicon-o-gift')
                ->color('warning'),
            Stat::make('Weiterleitungen', Redirect::count())
                ->icon('heroicon-o-arrow-turn-down-right')
                ->color('gray'),
        ];
    }
}
