<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Components\GlobalBarcodeScanner;
use App\Models\Product;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Helpers\FormatHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->minLength(2)
                            ->label('Product Name')
                            ->placeholder('e.g., iPhone 15, Samsung TV, Nike Shoes')
                            ->rules([
                                'required',
                                'string',
                                'min:2',
                                'max:255',
                            ]),
                        GlobalBarcodeScanner::make('barcode')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules([
                                'required',
                                'string',
                                'max:255',
                                'regex:/^[A-Za-z0-9\-_]+$/', // Only alphanumeric, hyphens, and underscores
                            ])
                            ->validationMessages([
                                'regex' => 'Barcode can only contain letters, numbers, hyphens, and underscores.',
                            ])
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Auto-fill product details if barcode exists
                                if ($state) {
                                    $existingProduct = Product::where('barcode', $state)->first();
                                    if ($existingProduct) {
                                        $set('name', $existingProduct->name);
                                        $set('category_id', $existingProduct->category_id);
                                        $set('subcategory_id', $existingProduct->subcategory_id);
                                        $set('provider_id', $existingProduct->provider_id);
                                        $set('purchase_price_per_unit', $existingProduct->purchase_price_per_unit);
                                        $set('sell_price_per_unit', $existingProduct->sell_price_per_unit);
                                        $set('low_stock_threshold', $existingProduct->low_stock_threshold);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name', function (Builder $query) {
                                return $query->whereNull('parent_id'); // Only show top-level categories
                            })
                            ->searchable()
                            ->preload()
                            ->label('Parent Category')
                            ->placeholder('Select a parent category')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear subcategory when parent changes
                                $set('subcategory_id', null);
                            }),
                        Forms\Components\Select::make('subcategory_id')
                            ->label('Subcategory')
                            ->placeholder('Select a subcategory (optional)')
                            ->options(function ($get) {
                                $parentId = $get('category_id');
                                if (!$parentId) return [];
                                
                                return Category::where('parent_id', $parentId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('provider_id')
                            ->relationship('provider', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Provider')
                            ->placeholder('Select a provider'),
                    ])->columns(2),
                Forms\Components\Section::make('Pricing & Stock Information')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price_per_unit')
                            ->required()
                            ->numeric()
                            ->label('Purchase Price per Unit')
                            ->prefix('$')
                            ->placeholder('0.00')
                            ->minValue(0)
                            ->step(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Update product units pricing when base price changes
                                $productUnits = $get('productUnits') ?? [];
                                if (!empty($productUnits)) {
                                    foreach ($productUnits as $index => $unit) {
                                        if (isset($unit['conversion_factor']) && $unit['conversion_factor'] > 0) {
                                            $productUnits[$index]['purchase_price'] = $state * $unit['conversion_factor'];
                                            $productUnits[$index]['sell_price'] = ($get('sell_price_per_unit') ?? 0) * $unit['conversion_factor'];
                                        }
                                    }
                                    $set('productUnits', $productUnits);
                                }
                            })
                            ->rules([
                                'required',
                                'numeric',
                                'min:0',
                            ]),
                        Forms\Components\TextInput::make('sell_price_per_unit')
                            ->required()
                            ->numeric()
                            ->label('Sell Price per Unit')
                            ->prefix('$')
                            ->placeholder('0.00')
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Update product units pricing when base price changes
                                $productUnits = $get('productUnits') ?? [];
                                if (!empty($productUnits)) {
                                    foreach ($productUnits as $index => $unit) {
                                        if (isset($unit['conversion_factor']) && $unit['conversion_factor'] > 0) {
                                            $productUnits[$index]['sell_price'] = $state * $unit['conversion_factor'];
                                        }
                                    }
                                    $set('productUnits', $productUnits);
                                }
                            })
                            ->rules([
                                'required',
                                'numeric',
                                'min:0',
                            ]),
                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->label('Current Stock')
                            ->default(0)
                            ->readOnly()
                            ->placeholder('0'),
                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->numeric()
                            ->label('Low Stock Threshold')
                            ->default(10)
                            ->placeholder('10')
                            ->helperText('Product will be marked as low stock when current stock reaches this level')
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive products won\'t appear in sales'),
                    ])->columns(2),
                Forms\Components\Section::make('Product Units')
                    ->schema([
                        Forms\Components\Repeater::make('productUnits')
                            ->relationship('productUnits')
                            ->schema([
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
                                    ->step(1)
                                    ->minValue(1)
                                    ->required()
                                    ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state, 0))
                                    ->helperText('How many base units this unit represents (e.g., 1 box = 12 pieces)')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get, $context) {
                                        // Auto-calculate prices based on conversion factor
                                        if ($state && $state > 0) {
                                            $basePurchasePrice = $get('../../purchase_price_per_unit') ?? 0;
                                            $baseSellPrice = $get('../../sell_price_per_unit') ?? 0;
                                            
                                            $set('purchase_price', $basePurchasePrice * $state);
                                            $set('sell_price', $baseSellPrice * $state);
                                        }
                                    })
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:1',
                                    ]),
                                Forms\Components\TextInput::make('purchase_price')
                                    ->numeric()
                                    ->label('Purchase Price for this Unit')
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Purchase price for this specific unit')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get, $context) {
                                        // Update base unit price if this is the base unit
                                        if ($get('is_base_unit')) {
                                            $set('../../purchase_price_per_unit', $state);
                                        }
                                    })
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:0',
                                    ]),
                                Forms\Components\TextInput::make('sell_price')
                                    ->numeric()
                                    ->label('Sell Price for this Unit')
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Sell price for this specific unit')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get, $context) {
                                        // Update base unit price if this is the base unit
                                        if ($get('is_base_unit')) {
                                            $set('../../sell_price_per_unit', $state);
                                        }
                                    })
                                    ->rules([
                                        'required',
                                        'numeric',
                                        'min:0',
                                    ]),
                                Forms\Components\Toggle::make('is_base_unit')
                                    ->label('Is Base Unit')
                                    ->default(false)
                                    ->helperText('Mark this as the base unit for conversions')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get, $context) {
                                        if ($state) {
                                            // Update base unit prices when this becomes the base unit
                                            $set('../../purchase_price_per_unit', $get('purchase_price'));
                                            $set('../../sell_price_per_unit', $get('sell_price'));
                                        }
                                    }),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive units won\'t appear in sales'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->default([
                                [
                                    'name' => 'Piece',
                                    'abbreviation' => 'pcs',
                                    'conversion_factor' => 1,
                                    'purchase_price' => null, // will be auto-calculated
                                    'sell_price' => null,     // will be auto-calculated
                                    'is_base_unit' => true,
                                    'is_active' => true,
                                ]
                            ])
                            ->createItemButtonLabel('Add Unit')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->afterStateUpdated(function ($state, $set) {
                                // Ensure only one base unit per product
                                $baseUnitCount = collect($state)->where('is_base_unit', true)->count();
                                if ($baseUnitCount > 1) {
                                    // Find the first base unit and keep it, uncheck others
                                    $firstBaseUnitIndex = collect($state)->search(function ($item) {
                                        return $item['is_base_unit'] ?? false;
                                    });
                                    
                                    if ($firstBaseUnitIndex !== false) {
                                        foreach ($state as $index => $item) {
                                            if ($index !== $firstBaseUnitIndex && ($item['is_base_unit'] ?? false)) {
                                                $state[$index]['is_base_unit'] = false;
                                            }
                                        }
                                        $set('productUnits', $state);
                                    }
                                }
                            }),
                    ])->collapsible(),
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
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('Barcode'),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No Category')
                    ->label('Parent Category'),
                Tables\Columns\TextColumn::make('subcategory.name')
                    ->label('Subcategory')
                    ->badge()
                    ->color('success')
                    ->placeholder('None'),
                Tables\Columns\TextColumn::make('provider.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->placeholder('No Provider'),
                Tables\Columns\TextColumn::make('purchase_price_per_unit')
                    ->label('Purchase Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sell_price_per_unit')
                    ->label('Sell Price')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Profit Margin')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->profit_margin !== null) {
                            return FormatHelper::formatPercentage($record->profit_margin);
                        }
                        return 'N/A';
                    })
                    ->color(function ($state, $record) {
                        return $record ? $record->profit_margin_color : 'gray';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => $record->stock_status_color)
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state, 0)),
                Tables\Columns\TextColumn::make('units_count')
                    ->label('Units')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state, $record) {
                        return $record ? $record->units_count : 0;
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Filter by Parent Category')
                    ->options(function () {
                        return Category::whereNull('parent_id')->pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Parent Categories'),
                Tables\Filters\SelectFilter::make('subcategory_id')
                    ->label('Filter by Subcategory')
                    ->options(function () {
                        return Category::whereNotNull('parent_id')->pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Subcategories'),
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Filter by Provider')
                    ->options(function () {
                        return Provider::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Providers'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Products')
                    ->query(fn (Builder $query): Builder => $query->lowStock() ->orWhere('stock', 0)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->outOfStock()),
                Tables\Filters\Filter::make('active_only')
                    ->label('Active Products Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->modalHeading('Edit Product')
                        ->modalDescription('Update product information.')
                        ->modalSubmitActionLabel('Update Product'),
                    Tables\Actions\Action::make('scan_barcode')
                        ->label('Scan Barcode')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->form([
                            GlobalBarcodeScanner::make('barcode')
                                ->required()
                                ->autofocus(),
                        ])
                        ->action(function (array $data, Product $record): void {
                            $record->update(['barcode' => $data['barcode']]);
                        })
                        ->modalHeading('Scan Barcode')
                        ->modalDescription('Update the product barcode.')
                        ->modalSubmitActionLabel('Update Barcode')
                        ->successNotificationTitle('Barcode updated successfully!'),
                ]),
            ])
            ->headerActions([
               //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->purchaseInvoiceItems()->count() > 0 || $record->saleItems()->count() > 0) {
                                    throw new \Exception("Cannot delete product '{$record->name}' because it has related records. Please remove them first.");
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductStocksRelationManager::class,
            RelationManagers\PriceHistoriesRelationManager::class,
            RelationManagers\ProductUnitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'subcategory', 'provider', 'productUnits', 'productStocks'])
            ->withCount(['productUnits', 'productStocks']);
    }

    // Authorization
    public static function canCreate(): bool
    {
        return auth()->user()->can('create products');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit products');
    }

    public static function canDelete(Model $record): bool
    {
        if ($record->purchaseInvoiceItems()->count() > 0 || $record->saleItems()->count() > 0) {
            return false;
        }
        return auth()->user()->can('delete products');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view products');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view products');
    }

    // Helper methods
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getModelLabel(): string
    {
        return 'Product';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Products';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'barcode'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name . ' (' . $record->barcode . ')';
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }
}
