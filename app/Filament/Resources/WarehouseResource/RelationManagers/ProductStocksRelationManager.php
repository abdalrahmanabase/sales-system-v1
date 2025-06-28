<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;
use App\Models\Category;
use App\Helpers\FormatHelper;

class ProductStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'productStocks';

    public function form(Form $form): Form
    {
        return $form->schema([]); // Stock is not created/edited directly here
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatQuantity($state))
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state <= 10 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('product.sell_price_per_unit')
                    ->label('Unit Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_updated_at')
                    ->label('Last Updated')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(Product::pluck('name', 'id')->toArray()),
                Tables\Filters\SelectFilter::make('product.category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id')->toArray()),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (<=10)')
                    ->query(fn (Builder $query) => $query->where('quantity', '<=', 10)),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
