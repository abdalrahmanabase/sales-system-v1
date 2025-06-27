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

class PriceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'priceHistories';
    protected static ?string $title = 'Price History';

    public function form(Form $form): Form
    {
        return $form->schema([
            // No form for inline editing
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('old_purchase_price')->money('USD')->label('Old Purchase'),
                TextColumn::make('new_purchase_price')->money('USD')->label('New Purchase'),
                TextColumn::make('old_sell_price')->money('USD')->label('Old Sell'),
                TextColumn::make('new_sell_price')->money('USD')->label('New Sell'),
                TextColumn::make('change_reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'invoice_update' => 'primary',
                        'manual_update' => 'success',
                        'system_update' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('changedBy.name')->label('Changed By')->placeholder('System'),
                TextColumn::make('changed_at')->dateTime()->label('Changed At'),
            ])
            ->defaultSort('changed_at', 'desc');
    }
}
