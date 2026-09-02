<?php

namespace App\Filament\Resources\ProductAudienceResource\Pages;

use App\Filament\Resources\ProductAudienceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductAudiences extends ListRecords
{
    protected static string $resource = ProductAudienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
