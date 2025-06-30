<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Helpers\FormatHelper;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branch Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Branch Name')
                            ->placeholder('e.g., Main Branch, Downtown Branch'),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255)
                            ->label('City')
                            ->placeholder('e.g., New York, Los Angeles'),
                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->maxLength(1000)
                            ->label('Full Address')
                            ->placeholder('Enter complete address including street, building, etc.'),
                        Forms\Components\TextInput::make('manager_name')
                            ->maxLength(255)
                            ->label('Manager Name')
                            ->placeholder('e.g., John Doe'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('manager_name')
                    ->searchable()
                    ->sortable()
                    ->label('Manager'),
                Tables\Columns\TextColumn::make('warehouses_count')
                    ->counts('warehouses')
                    ->label('Warehouses')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->counts('employees')
                    ->label('Employees')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sales_count')
                    ->counts('sales')
                    ->label('Sales')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_sales')
                        ->label('View Sales')
                        ->icon('heroicon-o-shopping-cart')
                        ->url(fn (Branch $record): string => route('filament.admin.resources.branches.view', ['record' => $record])),
                    Tables\Actions\Action::make('view_employees')
                        ->label('View Employees')
                        ->icon('heroicon-o-users')
                        ->url(fn (Branch $record): string => route('filament.admin.resources.branches.view', ['record' => $record])),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // You can add relation managers here for warehouses, employees, sales, etc.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['warehouses', 'employees', 'sales', 'revenues', 'expenses']);
    }

    // Authorization - Use permissions instead of role checks
    public static function canCreate(): bool
    {
        return auth()->user()->can('create branches');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit branches');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete branches');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view branches');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view branches');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }
}
