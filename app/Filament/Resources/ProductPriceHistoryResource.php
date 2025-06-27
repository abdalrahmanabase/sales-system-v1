<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductPriceHistoryResource\Pages;
use App\Filament\Resources\ProductPriceHistoryResource\RelationManagers;
use App\Models\ProductPriceHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductPriceHistoryResource extends Resource
{
    protected static ?string $model = ProductPriceHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Price History';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required()
                    ->label('Product'),
                Forms\Components\TextInput::make('old_purchase_price')
                    ->numeric()
                    ->prefix('$')
                    ->label('Old Purchase Price'),
                Forms\Components\TextInput::make('new_purchase_price')
                    ->numeric()
                    ->prefix('$')
                    ->label('New Purchase Price'),
                Forms\Components\TextInput::make('old_sell_price')
                    ->numeric()
                    ->prefix('$')
                    ->label('Old Sell Price'),
                Forms\Components\TextInput::make('new_sell_price')
                    ->numeric()
                    ->prefix('$')
                    ->label('New Sell Price'),
                Forms\Components\DateTimePicker::make('changed_at')
                    ->required()
                    ->label('Changed At'),
                Forms\Components\Select::make('changed_by')
                    ->relationship('changedBy', 'name')
                    ->searchable()
                    ->label('Changed By'),
                Forms\Components\Select::make('change_reason')
                    ->options([
                        'invoice_update' => 'Invoice Update',
                        'manual_update' => 'Manual Update',
                        'system_update' => 'System Update',
                    ])
                    ->label('Change Reason'),
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->label('Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->label('Product')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('product.barcode')
                    ->searchable()
                    ->sortable()
                    ->label('Barcode')
                    ->copyable(),
                Tables\Columns\TextColumn::make('old_purchase_price')
                    ->money('USD')
                    ->label('Old Purchase')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('new_purchase_price')
                    ->money('USD')
                    ->label('New Purchase')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('old_sell_price')
                    ->money('USD')
                    ->label('Old Sell')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('new_sell_price')
                    ->money('USD')
                    ->label('New Sell')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('price_change_description')
                    ->label('Price Changes')
                    ->badge()
                    ->color('info')
                    ->wrap(),
                Tables\Columns\TextColumn::make('changed_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Changed At'),
                Tables\Columns\TextColumn::make('changedBy.name')
                    ->label('Changed By')
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('change_reason')
                    ->label('Reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'invoice_update' => 'primary',
                        'manual_update' => 'success',
                        'system_update' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Filter by Product')
                    ->options(fn () => \App\Models\Product::pluck('name', 'id')->toArray())
                    ->placeholder('All Products'),
                Tables\Filters\SelectFilter::make('change_reason')
                    ->label('Filter by Reason')
                    ->options([
                        'invoice_update' => 'Invoice Update',
                        'manual_update' => 'Manual Update',
                        'system_update' => 'System Update',
                    ])
                    ->placeholder('All Reasons'),
                Tables\Filters\Filter::make('purchase_price_changed')
                    ->label('Purchase Price Changed')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('old_purchase_price != new_purchase_price')),
                Tables\Filters\Filter::make('sell_price_changed')
                    ->label('Sell Price Changed')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('old_sell_price != new_sell_price')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('changed_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductPriceHistories::route('/'),
            'view' => Pages\ViewProductPriceHistory::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'changedBy']);
    }
}
