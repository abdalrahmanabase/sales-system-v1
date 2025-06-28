<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use App\Helpers\FormatHelper;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Invoice Items';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->required()
                ->label('Product'),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required()
                ->minValue(1)
                ->label('Quantity'),
            Forms\Components\TextInput::make('purchase_price')
                ->numeric()
                ->required()
                ->prefix('$')
                ->label('Purchase Price'),
            Forms\Components\TextInput::make('sell_price')
                ->numeric()
                ->required()
                ->prefix('$')
                ->label('Sell Price'),
            Forms\Components\Toggle::make('is_bonus')
                ->label('Bonus Item')
                ->helperText('If checked, cost will be zero but purchase price is still recorded for tracking')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->label('Product')
                    ->weight('bold'),
                TextColumn::make('product.barcode')
                    ->searchable()
                    ->sortable()
                    ->label('Barcode')
                    ->copyable(),
                TextColumn::make('quantity')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatQuantity($state))
                    ->sortable()
                    ->label('Quantity'),
                TextColumn::make('purchase_price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->label('Purchase Price')
                    ->description(fn ($record) => $record->is_bonus ? 'Bonus item - cost is zero' : ''),
                TextColumn::make('sell_price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->label('Sell Price'),
                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->getStateUsing(function ($record) {
                        return $record->total_cost;
                    })
                    ->sortable()
                    ->description(fn ($record) => $record->is_bonus ? 'Bonus item' : ''),
                TextColumn::make('total_purchase_value')
                    ->label('Purchase Value')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->getStateUsing(function ($record) {
                        return $record->total_purchase_value;
                    })
                    ->sortable()
                    ->description(fn ($record) => $record->is_bonus ? 'Actual value for tracking' : ''),
                IconColumn::make('is_bonus')
                    ->boolean()
                    ->label('Bonus')
                    ->trueIcon('heroicon-o-gift')
                    ->falseIcon('heroicon-o-x-mark'),
            ])
            ->filters([
                Tables\Filters\Filter::make('bonus_items')
                    ->label('Bonus Items Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_bonus', true)),
                Tables\Filters\Filter::make('non_bonus_items')
                    ->label('Non-Bonus Items Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_bonus', false)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Add Invoice Item')
                    ->modalDescription('Add a new item to this purchase invoice.')
                    ->modalSubmitActionLabel('Add Item'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Invoice Item')
                    ->modalDescription('Update this invoice item.')
                    ->modalSubmitActionLabel('Update Item'),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Decrease stock when item is deleted (for all items, including bonus)
                        $record->product->decrement('stock', $record->quantity);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                // Decrease stock when items are deleted (for all items, including bonus)
                                $record->product->decrement('stock', $record->quantity);
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Authorization
    public function canCreate(): bool
    {
        return auth()->user()->can('edit purchase invoices');
    }

    public function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit purchase invoices');
    }

    public function canDelete(Model $record): bool
    {
        return auth()->user()->can('edit purchase invoices');
    }

    public function canViewAny(): bool
    {
        return auth()->user()->can('view purchase invoices');
    }

    public function canView(Model $record): bool
    {
        return auth()->user()->can('view purchase invoices');
    }
}
