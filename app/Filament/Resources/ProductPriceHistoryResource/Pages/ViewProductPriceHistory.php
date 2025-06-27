<?php

namespace App\Filament\Resources\ProductPriceHistoryResource\Pages;

use App\Filament\Resources\ProductPriceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductPriceHistory extends ViewRecord
{
    protected static string $resource = ProductPriceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 