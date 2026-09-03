<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAudienceResource\Pages;
use App\Filament\Resources\ProductAudienceResource\RelationManagers;
use App\Models\ProductAudience;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductAudienceResource extends Resource
{
    protected static ?string $model = ProductAudience::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Taxonomien';

    protected static ?string $modelLabel = 'Zielgruppe';

    protected static ?string $pluralModelLabel = 'Zielgruppen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductAudiences::route('/'),
            'create' => Pages\CreateProductAudience::route('/create'),
            'edit' => Pages\EditProductAudience::route('/{record}/edit'),
        ];
    }
}
