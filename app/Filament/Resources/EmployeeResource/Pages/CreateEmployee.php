<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate employee ID if not provided
        if (empty($data['employee_id'])) {
            $data['employee_id'] = 'EMP' . str_pad(
                (\App\Models\Employee::max('id') ?? 0) + 1,
                4,
                '0',
                STR_PAD_LEFT
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}