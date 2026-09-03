<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Seite';

    protected static ?string $pluralModelLabel = 'Seiten';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Titel')->required(),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Bestimmt die URL: /slug/'),
            Forms\Components\RichEditor::make('body_html')
                ->label('Inhalt')
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('meta_description')
                ->label('Meta-Beschreibung')
                ->maxLength(160)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
