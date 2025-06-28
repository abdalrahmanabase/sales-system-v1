<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Helpers\FormatHelper;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form->schema([]); // Products are managed in ProductResource
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price_per_unit')
                    ->label('Purchase Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sell_price_per_unit')
                    ->label('Sell Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatQuantity($state))
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state <= 10 ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active Products')
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive Products')
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (<=10)')
                    ->query(fn (Builder $query) => $query->where('stock', '<=', 10)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view_product')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
