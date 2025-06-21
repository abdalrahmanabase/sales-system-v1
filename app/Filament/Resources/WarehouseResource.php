<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Warehouse Information')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Branch')
                            ->placeholder('Select a branch'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Warehouse Name')
                            ->placeholder('e.g., Main Warehouse, Storage A'),
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
                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('branch.city')
                    ->label('City')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('product_stocks_count')
                    ->counts('productStocks')
                    ->label('Products in Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_stock_value')
                    ->label('Total Stock Value')
                    ->getStateUsing(function (Warehouse $record) {
                        return $record->productStocks()
                            ->join('products', 'product_stocks.product_id', '=', 'products.id')
                            ->sum(DB::raw('product_stocks.quantity * products.sell_price_per_unit'));
                    })
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('low_stock_products')
                    ->label('Low Stock Products')
                    ->getStateUsing(function (Warehouse $record) {
                        return $record->productStocks()
                            ->where('quantity', '<=', 10)
                            ->count();
                    })
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filter by Branch')
                    ->options(function () {
                        return Branch::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Branches'),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Filter by City')
                    ->options(function () {
                        return Branch::distinct()->pluck('city', 'city')->filter()->toArray();
                    })
                    ->placeholder('All Cities'),
                Tables\Filters\Filter::make('has_stock')
                    ->label('Has Stock')
                    ->query(fn (Builder $query): Builder => $query->whereHas('productStocks', fn($q) => $q->where('quantity', '>', 0))),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->whereHas('productStocks', fn($q) => $q->where('quantity', '<=', 10))),
                Tables\Filters\Filter::make('no_stock')
                    ->label('No Stock')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('productStocks', fn($q) => $q->where('quantity', '>', 0))),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_stock')
                        ->label('View Stock')
                        ->icon('heroicon-o-cube')
                        ->url(fn (Warehouse $record): string => route('filament.admin.resources.warehouses.view', ['record' => $record])),
                    Tables\Actions\Action::make('stock_adjustment')
                        ->label('Stock Adjustment')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->url(fn (Warehouse $record): string => route('filament.admin.resources.warehouses.edit', ['record' => $record])),
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
            // We'll add relation managers for stock management
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'view' => Pages\ViewWarehouse::route('/{record}'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['branch', 'productStocks.product', 'productStocks.product.category'])
            ->withCount('productStocks');
    }

    // Authorization
    public static function canCreate(): bool
    {
        return auth()->user()->can('create warehouses');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit warehouses');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete warehouses');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view warehouses');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view warehouses');
    }
}
