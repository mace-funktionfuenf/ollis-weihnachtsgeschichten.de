<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Filament\Resources\RedirectResource\RelationManagers;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-turn-down-right';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Weiterleitung';

    protected static ?string $pluralModelLabel = 'Weiterleitungen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('from_path')
                    ->label('Von (Pfad)')
                    ->required(),
                Forms\Components\TextInput::make('to_path')
                    ->label('Nach (Pfad oder URL)')
                    ->required(),
                Forms\Components\TextInput::make('status_code')
                    ->label('Status-Code')
                    ->required()
                    ->numeric()
                    ->default(301),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_path')
                    ->label('Von (Pfad)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_path')
                    ->label('Nach (Pfad oder URL)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status-Code')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
