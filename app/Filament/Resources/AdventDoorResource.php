<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdventDoorResource\Pages;
use App\Models\AdventDoor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdventDoorResource extends Resource
{
    protected static ?string $model = AdventDoor::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Adventstürchen';

    protected static ?string $pluralModelLabel = 'Adventskalender';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('day')
                ->label('Türchen (Tag)')
                ->options(array_combine(range(1, 24), range(1, 24)))
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Öffnet sich für Besucher automatisch ab diesem Tag im Dezember.'),
            Forms\Components\TextInput::make('title')
                ->label('Titel')
                ->required()
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('story_html')
                ->label('Geschichte')
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('day')
            ->columns([
                Tables\Columns\TextColumn::make('day')->label('Tag')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdventDoors::route('/'),
            'create' => Pages\CreateAdventDoor::route('/create'),
            'edit' => Pages\EditAdventDoor::route('/{record}/edit'),
        ];
    }
}
