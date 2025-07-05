<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Employees'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)->where('employment_status', 'active')),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)->orWhere('employment_status', '!=', 'active')),
            'resigned' => Tab::make('Resigned')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('employment_status', 'resigned')),
            'terminated' => Tab::make('Terminated')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('employment_status', 'terminated')),
        ];
    }
}