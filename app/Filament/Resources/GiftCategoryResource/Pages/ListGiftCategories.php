<?php

namespace App\Filament\Resources\GiftCategoryResource\Pages;

use App\Filament\Resources\GiftCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGiftCategories extends ListRecords
{
    protected static string $resource = GiftCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
