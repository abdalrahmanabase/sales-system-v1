<?php

namespace App\Filament\Resources\ProductPriceHistoryResource\Pages;

use App\Filament\Resources\ProductPriceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductPriceHistories extends ListRecords
{
    protected static string $resource = ProductPriceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
