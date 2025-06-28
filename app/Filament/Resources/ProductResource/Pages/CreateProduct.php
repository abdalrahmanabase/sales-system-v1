<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Create the product first
        $product = static::getModel()::create($data);

        // Handle product units if provided
        if (isset($data['productUnits']) && is_array($data['productUnits'])) {
            foreach ($data['productUnits'] as $unitData) {
                if (!empty($unitData['name'])) {
                    $product->productUnits()->create($unitData);
                }
            }
        }

        // The Product model's boot() method automatically creates a default unit
        // No need to call ensureDefaultUnitExists() here

        return $product;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
