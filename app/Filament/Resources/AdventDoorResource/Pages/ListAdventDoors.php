<?php

namespace App\Filament\Resources\AdventDoorResource\Pages;

use App\Filament\Resources\AdventDoorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdventDoors extends ListRecords
{
    protected static string $resource = AdventDoorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
