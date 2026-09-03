<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Beitrag';

    protected static ?string $pluralModelLabel = 'Beiträge';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titel')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', str($state)->slug()) : null)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Bestimmt die URL: /slug/ - nach Veröffentlichung nicht mehr ändern.'),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(['publish' => 'Veröffentlicht', 'draft' => 'Entwurf', 'private' => 'Privat'])
                        ->default('draft')
                        ->required(),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Veröffentlicht am')
                        ->native(false),
                    Forms\Components\TextInput::make('author_name')
                        ->label('Autor')
                        ->default('Olaf Taubert'),
                ]),
            Forms\Components\RichEditor::make('body_html')
                ->label('Inhalt')
                ->required()
                ->extraInputAttributes(['style' => 'max-height: 32rem; overflow-y: auto;'])
                ->columnSpanFull(),
            Forms\Components\Textarea::make('excerpt')
                ->label('Auszug')
                ->columnSpanFull(),
            Forms\Components\Section::make('Hero-Bild')
                ->schema([
                    Forms\Components\Select::make('existing_featured_image')
                        ->label('Vorhandenes Bild wählen')
                        ->helperText('Wählt ein bereits hochgeladenes Bild erneut aus, statt es neu hochzuladen.')
                        ->options(fn () => collect(Storage::disk('public')->files('posts'))
                            ->mapWithKeys(fn (string $path) => [$path => self::existingImageOptionLabel($path)]))
                        // allowHtml() makes Choices.js's client-side fuzzy search match
                        // against the raw <img src="..."> markup instead of the filename,
                        // which reliably finds zero results once labels contain a full
                        // URL - a server-side search callback sidesteps that entirely.
                        ->getSearchResultsUsing(fn (string $search) => collect(Storage::disk('public')->files('posts'))
                            ->filter(fn (string $path) => str_contains(strtolower(basename($path)), strtolower($search)))
                            ->take(50)
                            ->mapWithKeys(fn (string $path) => [$path => self::existingImageOptionLabel($path)]))
                        ->getOptionLabelUsing(fn (?string $value) => $value ? self::existingImageOptionLabel($value) : null)
                        ->allowHtml()
                        ->searchable()
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, callable $set) {
                            // FileUpload's own live state is never a plain path - it's an
                            // array keyed by a UUID (the shape it hydrates an existing DB
                            // value into on load, per BaseFileUpload::setUp()). Writing a
                            // raw string here bypasses that and crashes FileUpload's next
                            // render with "foreach() argument must be of type array".
                            if ($state) {
                                $set('featured_image', [(string) \Illuminate\Support\Str::uuid() => $state]);
                            }
                        }),
                    Forms\Components\FileUpload::make('featured_image')
                        ->label('Neues Bild hochladen')
                        ->image()
                        ->directory('posts')
                        ->saveUploadedFileUsing(fn ($file) => \App\Support\ImageOptimizer::storeAndOptimize($file, 'posts')),
                ]),
            Forms\Components\Section::make('Kategorien & Schlagworte')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('categories')
                        ->label('Kategorien')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->preload(),
                    Forms\Components\Select::make('tags')
                        ->label('Schlagworte')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload(),
                ]),
            Forms\Components\Select::make('products')
                ->label('Beworbene Produkte')
                ->helperText('Wird am Ende des Beitrags als Produktempfehlung angezeigt.')
                ->relationship('products', 'title')
                ->multiple()
                ->preload()
                ->searchable()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('meta_description')
                ->label('Meta-Beschreibung')
                ->maxLength(160)
                ->helperText('SEO-Beschreibung (max. 160 Zeichen).')
                ->columnSpanFull(),
        ]);
    }

    private static function existingImageOptionLabel(string $path): string
    {
        return '<div style="display:flex;align-items:center;gap:0.5rem;">'
            .'<img src="'.e(Storage::disk('public')->url($path)).'" style="width:2.5rem;height:2.5rem;object-fit:cover;border-radius:0.25rem;flex-shrink:0;">'
            .'<span>'.e(basename($path)).'</span>'
            .'</div>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('published_at')->label('Veröffentlicht am')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['publish' => 'Veröffentlicht', 'draft' => 'Entwurf', 'private' => 'Privat']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
