<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle parent_id from URL parameter
        if (request()->has('parent_id')) {
            $data['parent_id'] = request()->get('parent_id');
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Category created successfully';
    }
}
