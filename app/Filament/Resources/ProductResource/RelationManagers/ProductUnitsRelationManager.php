<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\FormatHelper;

class ProductUnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'productUnits';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Unit Name')
                ->placeholder('e.g., Piece, Box, Pack'),
            Forms\Components\TextInput::make('abbreviation')
                ->maxLength(10)
                ->label('Abbreviation')
                ->placeholder('e.g., pcs, box, pack'),
            Forms\Components\TextInput::make('conversion_factor')
                ->numeric()
                ->label('Conversion Factor')
                ->default(1)
                ->step(0.0001)
                ->minValue(0.0001)
                ->required()
                ->helperText('How many base units this unit represents (e.g., 1 box = 12 pieces)')
                ->live()
                ->afterStateUpdated(function ($state, $set, $get, $record) {
                    // Auto-calculate prices based on conversion factor
                    if ($state && $state > 0) {
                        $product = $this->getOwnerRecord();
                        $basePurchasePrice = $product->purchase_price_per_unit ?? 0;
                        $baseSellPrice = $product->sell_price_per_unit ?? 0;
                        
                        $set('purchase_price', $basePurchasePrice * $state);
                        $set('sell_price', $baseSellPrice * $state);
                    }
                })
                ->rules([
                    'required',
                    'numeric',
                    'min:0.0001',
                ]),
            Forms\Components\TextInput::make('purchase_price')
                ->numeric()
                ->label('Purchase Price for this Unit')
                ->prefix('$')
                ->placeholder('0.00')
                ->helperText('Purchase price for this specific unit')
                ->live()
                ->afterStateUpdated(function ($state, $set, $get, $record) {
                    // Update base unit price if this is the base unit
                    if ($get('is_base_unit')) {
                        $product = $this->getOwnerRecord();
                        $product->update(['purchase_price_per_unit' => $state]);
                    }
                }),
            Forms\Components\TextInput::make('sell_price')
                ->numeric()
                ->label('Sell Price for this Unit')
                ->prefix('$')
                ->placeholder('0.00')
                ->helperText('Sell price for this specific unit')
                ->live()
                ->afterStateUpdated(function ($state, $set, $get, $record) {
                    // Update base unit price if this is the base unit
                    if ($get('is_base_unit')) {
                        $product = $this->getOwnerRecord();
                        $product->update(['sell_price_per_unit' => $state]);
                    }
                }),
            Forms\Components\Toggle::make('is_base_unit')
                ->label('Is Base Unit')
                ->default(false)
                ->helperText('Mark this as the base unit for conversions')
                ->live()
                ->afterStateUpdated(function ($state, $set, $get, $record) {
                    if ($state && $record) {
                        // If this is being set as base unit, unset others
                        $record->product->productUnits()
                            ->where('id', '!=', $record->id)
                            ->update(['is_base_unit' => false]);
                        
                        // Update base unit prices when this becomes the base unit
                        $set('purchase_price', $get('purchase_price'));
                        $set('sell_price', $get('sell_price'));
                        
                        $product = $this->getOwnerRecord();
                        $product->update([
                            'purchase_price_per_unit' => $get('purchase_price'),
                            'sell_price_per_unit' => $get('sell_price')
                        ]);
                    }
                }),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive units won\'t appear in sales'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->sortable()
                ->searchable()
                ->weight('bold'),
            Tables\Columns\TextColumn::make('abbreviation')
                ->sortable()
                ->searchable()
                ->badge()
                ->color('info'),
            Tables\Columns\TextColumn::make('conversion_factor')
                ->sortable()
                ->label('Conversion')
                ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state) . 'x'),
            Tables\Columns\TextColumn::make('purchase_price')
                ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                ->sortable()
                ->label('Purchase Price'),
            Tables\Columns\TextColumn::make('sell_price')
                ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                ->sortable()
                ->label('Sell Price'),
            Tables\Columns\IconColumn::make('is_base_unit')
                ->boolean()
                ->label('Base Unit')
                ->trueIcon('heroicon-o-star')
                ->falseIcon('heroicon-o-star')
                ->trueColor('warning')
                ->falseColor('gray'),
            Tables\Columns\IconColumn::make('is_active')
                ->boolean()
                ->label('Active'),
        ])
        ->filters([
            Tables\Filters\Filter::make('base_units_only')
                ->label('Base Units Only')
                ->query(fn (Builder $query): Builder => $query->where('is_base_unit', true)),
            Tables\Filters\Filter::make('active_units_only')
                ->label('Active Units Only')
                ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
        ])
        ->headerActions([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make()
                ->before(function (array $data, $record) {
                    // Ensure only one base unit per product
                    if ($data['is_base_unit'] ?? false) {
                        $record->product->productUnits()
                            ->where('id', '!=', $record->id)
                            ->update(['is_base_unit' => false]);
                    }
                    
                    // Auto-calculate prices if conversion factor is provided
                    if (isset($data['conversion_factor']) && $data['conversion_factor'] > 0) {
                        $product = $this->getOwnerRecord();
                        $basePurchasePrice = $product->purchase_price_per_unit ?? 0;
                        $baseSellPrice = $product->sell_price_per_unit ?? 0;
                        
                        $data['purchase_price'] = $basePurchasePrice * $data['conversion_factor'];
                        $data['sell_price'] = $baseSellPrice * $data['conversion_factor'];
                    }
                })
                ->after(function ($record) {
                    // Update base unit prices if this is the base unit
                    if ($record->is_base_unit) {
                        $product = $this->getOwnerRecord();
                        $product->update([
                            'purchase_price_per_unit' => $record->purchase_price,
                            'sell_price_per_unit' => $record->sell_price
                        ]);
                    }
                }),
            Tables\Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Prevent deletion of base unit if it's the only unit
                    if ($record->is_base_unit) {
                        $unitCount = $record->product->productUnits()->count();
                        if ($unitCount <= 1) {
                            throw new \Exception('Cannot delete the base unit when it\'s the only unit for this product.');
                        }
                    }
                }),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make()
                ->before(function ($records) {
                    foreach ($records as $record) {
                        // Prevent deletion of base unit if it's the only unit
                        if ($record->is_base_unit) {
                            $unitCount = $record->product->productUnits()->count();
                            if ($unitCount <= 1) {
                                throw new \Exception("Cannot delete the base unit '{$record->name}' when it's the only unit for this product.");
                            }
                        }
                    }
                }),
        ])
        ->defaultSort('is_base_unit', 'desc')
        ->defaultSort('name', 'asc');
    }
} 