<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Category')
                ->icon('heroicon-o-pencil'),
            Actions\Action::make('create_subcategory')
                ->label('Add Subcategory')
                ->icon('heroicon-o-plus')
                ->url(fn () => route('filament.admin.resources.categories.create', ['parent_id' => $this->record->id]))
                ->visible(fn () => $this->record->isParent()),
        ];
    }
} 