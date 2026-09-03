<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPosts extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Neueste Beiträge';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Post::query()->latest('published_at'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('published_at')->label('Veröffentlicht am')->dateTime('d.m.Y')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->paginated([5, 10, 25]);
    }
}
