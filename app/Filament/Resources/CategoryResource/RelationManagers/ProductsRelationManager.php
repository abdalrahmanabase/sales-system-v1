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
    protected static ?string $title = 'Products in this Category';

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
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Barcode copied to clipboard'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('purchase_price_per_unit')
                    ->label('Purchase Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('sell_price_per_unit')
                    ->label('Sell Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatQuantity($state))
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state <= 10 ? 'danger' : ($state <= 50 ? 'warning' : 'success'))
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
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
                    ->label('Low Stock (≤10)')
                    ->query(fn (Builder $query) => $query->where('stock', '<=', 10)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),
                Tables\Filters\Filter::make('has_stock')
                    ->label('Has Stock')
                    ->query(fn (Builder $query) => $query->where('stock', '>', 0)),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view_product')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.products.view', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('edit_product')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('name', 'asc');
    }
}
