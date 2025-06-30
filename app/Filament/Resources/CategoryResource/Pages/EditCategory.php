<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Category')
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->label('Delete Category')
                ->icon('heroicon-o-trash')
                ->before(function () {
                    // Check if category has products
                    if ($this->record->products()->count() > 0) {
                        throw new \Exception("Cannot delete category '{$this->record->name}' because it has products. Please remove or reassign the products first.");
                    }
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Category updated successfully';
    }
}
