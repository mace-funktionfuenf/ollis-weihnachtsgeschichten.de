<?php

namespace App\Filament\Resources\AdventDoorResource\Pages;

use App\Filament\Resources\AdventDoorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdventDoor extends EditRecord
{
    protected static string $resource = AdventDoorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
