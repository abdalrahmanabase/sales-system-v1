<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Helpers\FormatHelper;

class PriceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'priceHistories';
    protected static ?string $title = 'Price History';

    public function form(Form $form): Form
    {
        return $form->schema([
            // No form for inline editing - price history is read-only
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('changed_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDate($state))
                    ->label('Date & Time')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->badge()
                    ->color('info')
                    ->placeholder('Base Unit'),
                TextColumn::make('price_change_summary')
                    ->label('Price Changes')
                    ->formatStateUsing(function ($record) {
                        if (!$record) return '';
                        $changes = [];
                        
                        if ($record->old_purchase_price != $record->new_purchase_price) {
                            $oldPurchase = FormatHelper::formatCurrency($record->old_purchase_price);
                            $newPurchase = FormatHelper::formatCurrency($record->new_purchase_price);
                            $changes[] = "Purchase: {$oldPurchase} → {$newPurchase}";
                        }
                        
                        if ($record->old_sell_price != $record->new_sell_price) {
                            $oldSell = FormatHelper::formatCurrency($record->old_sell_price);
                            $newSell = FormatHelper::formatCurrency($record->new_sell_price);
                            $changes[] = "Sell: {$oldSell} → {$newSell}";
                        }
                        
                        return implode(' | ', $changes);
                    })
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('purchase_price_change_percentage')
                    ->label('Purchase Δ%')
                    ->formatStateUsing(fn ($state) => $state ? FormatHelper::formatPercentage($state) : '0.00%')
                    ->color(fn ($state) => $state > 0 ? 'danger' : ($state < 0 ? 'success' : 'gray'))
                    ->badge()
                    ->visible(fn ($record) => $record && $record->old_purchase_price != $record->new_purchase_price),
                TextColumn::make('sell_price_change_percentage')
                    ->label('Sell Δ%')
                    ->formatStateUsing(fn ($state) => $state ? FormatHelper::formatPercentage($state) : '0.00%')
                    ->color(fn ($state) => $state > 0 ? 'danger' : ($state < 0 ? 'success' : 'gray'))
                    ->badge()
                    ->visible(fn ($record) => $record && $record->old_sell_price != $record->new_sell_price),
                TextColumn::make('price_change_direction')
                    ->label('Direction')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'danger',
                        'down' => 'success',
                        'mixed' => 'warning',
                        'no_change' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('change_reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'product_creation' => 'success',
                        'invoice_update' => 'primary',
                        'manual_update' => 'success',
                        'system_update' => 'warning',
                        'provider_update' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'provider' => 'info',
                        'sale' => 'success',
                        'return' => 'warning',
                        'transfer' => 'primary',
                        'adjustment' => 'danger',
                        'creation' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('Manual'),
                TextColumn::make('source_reference')
                    ->label('Reference')
                    ->placeholder('N/A')
                    ->copyable(),
                TextColumn::make('changedBy.name')
                    ->label('Changed By')
                    ->placeholder('System'),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->placeholder('No notes')
                    ->tooltip(function ($record) {
                        return $record && $record->notes ? $record->notes : 'No additional notes';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Filter by Unit')
                    ->options(function () {
                        return $this->getOwnerRecord()->productUnits()
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('All Units'),
                Tables\Filters\SelectFilter::make('change_reason')
                    ->label('Filter by Reason')
                    ->options([
                        'product_creation' => 'Product Creation',
                        'manual_update' => 'Manual Update',
                        'invoice_update' => 'Invoice Update',
                        'system_update' => 'System Update',
                        'provider_update' => 'Provider Update',
                    ])
                    ->placeholder('All Reasons'),
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Filter by Source')
                    ->options([
                        'creation' => 'Creation',
                        'provider' => 'Provider',
                        'sale' => 'Sale',
                        'return' => 'Return',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Adjustment',
                    ])
                    ->placeholder('All Sources'),
                Tables\Filters\Filter::make('price_increases')
                    ->label('Price Increases Only')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('new_purchase_price > old_purchase_price OR new_sell_price > old_sell_price')),
                Tables\Filters\Filter::make('price_decreases')
                    ->label('Price Decreases Only')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('new_purchase_price < old_purchase_price OR new_sell_price < old_sell_price')),
                Tables\Filters\Filter::make('purchase_price_changes')
                    ->label('Purchase Price Changes')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('old_purchase_price != new_purchase_price')),
                Tables\Filters\Filter::make('sell_price_changes')
                    ->label('Sell Price Changes')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('old_sell_price != new_sell_price')),
                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['start_date'] && $data['end_date']) {
                            return $query->whereBetween('changed_at', [$data['start_date'], $data['end_date']]);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Price Change Details')
                    ->modalDescription('View detailed information about this price change.')
                    ->modalContent(function ($record) {
                        return view('filament.components.price-change-details', [
                            'record' => $record,
                        ]);
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('changed_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No price changes recorded')
            ->emptyStateDescription('Price changes will appear here when the product prices are modified.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
