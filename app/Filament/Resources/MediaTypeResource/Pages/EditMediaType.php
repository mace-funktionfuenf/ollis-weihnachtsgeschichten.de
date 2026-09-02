<?php

namespace App\Filament\Resources\MediaTypeResource\Pages;

use App\Filament\Resources\MediaTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMediaType extends EditRecord
{
    protected static string $resource = MediaTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
