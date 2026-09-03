<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Produkt';

    protected static ?string $pluralModelLabel = 'Produkte';

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
                        ->helperText('Bestimmt die URL: /produkt/slug/'),
                    Forms\Components\Toggle::make('available')
                        ->label('Verfügbar')
                        ->default(true),
                    Forms\Components\TextInput::make('asin')->label('ASIN'),
                    Forms\Components\TextInput::make('ean')->label('EAN'),
                    Forms\Components\TextInput::make('price')->label('Preis')->numeric()->prefix('€'),
                    Forms\Components\TextInput::make('price_old')->label('Alter Preis')->numeric()->prefix('€'),
                    Forms\Components\TextInput::make('affiliate_link')
                        ->label('Amazon-Link (mit Partner-Tag)')
                        ->url()
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Produktbild')
                        ->image()
                        ->directory('products')
                        ->columnSpanFull(),
                ]),
            Forms\Components\RichEditor::make('body_html')
                ->label('Beschreibung')
                ->columnSpanFull(),
            Forms\Components\Section::make('Einordnung')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('audiences')
                        ->label('Für')
                        ->relationship('audiences', 'name')
                        ->multiple()
                        ->preload(),
                    Forms\Components\Select::make('giftCategories')
                        ->label('Geschenkkategorie')
                        ->relationship('giftCategories', 'name')
                        ->multiple()
                        ->preload(),
                    Forms\Components\Select::make('mediaTypes')
                        ->label('Medienart')
                        ->relationship('mediaTypes', 'name')
                        ->multiple()
                        ->preload(),
                ]),
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
                Tables\Columns\ImageColumn::make('image_path')->label(''),
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Preis')->money('EUR')->sortable(),
                Tables\Columns\IconColumn::make('available')->label('Verfügbar')->boolean(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
