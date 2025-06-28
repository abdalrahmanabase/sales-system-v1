<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update the product
        $record->update($data);

        // Handle product units updates if provided
        if (isset($data['productUnits']) && is_array($data['productUnits'])) {
            // Get existing unit IDs
            $existingUnitIds = $record->productUnits()->pluck('id')->toArray();
            $updatedUnitIds = [];

            foreach ($data['productUnits'] as $unitData) {
                if (!empty($unitData['name'])) {
                    if (isset($unitData['id'])) {
                        // Update existing unit
                        $record->productUnits()->where('id', $unitData['id'])->update($unitData);
                        $updatedUnitIds[] = $unitData['id'];
                    } else {
                        // Create new unit
                        $newUnit = $record->productUnits()->create($unitData);
                        $updatedUnitIds[] = $newUnit->id;
                    }
                }
            }

            // Delete units that were removed from the form
            $unitsToDelete = array_diff($existingUnitIds, $updatedUnitIds);
            if (!empty($unitsToDelete)) {
                $record->productUnits()->whereIn('id', $unitsToDelete)->delete();
            }
        }

        // Ensure at least one unit exists (but don't create duplicate defaults)
        if ($record->productUnits()->count() === 0) {
            $record->ensureDefaultUnitExists();
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
