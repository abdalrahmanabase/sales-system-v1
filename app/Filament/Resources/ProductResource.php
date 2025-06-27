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
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                            ->label('Product Name')
                            ->placeholder('e.g., iPhone 15, Samsung TV, Nike Shoes'),
                        GlobalBarcodeScanner::make('barcode')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
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
                                return $query->whereNull('parent_id'); // Only show parent categories
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
                            ->live()
                            ->rules([
                                function ($get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        $parentId = $get('category_id');
                                        if ($value && $parentId) {
                                            $subcategory = Category::find($value);
                                            if ($subcategory && $subcategory->parent_id != $parentId) {
                                                $fail('The selected subcategory does not belong to the selected parent category.');
                                            }
                                        }
                                    };
                                }
                            ]),
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
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('sell_price_per_unit')
                            ->required()
                            ->numeric()
                            ->label('Sell Price per Unit')
                            ->prefix('$')
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->label('Current Stock')
                            ->default(0)
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
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->createItemButtonLabel('Add Unit')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
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
                    ->money('USD')
                    ->sortable()
                    ->label('Purchase Price'),
                Tables\Columns\TextColumn::make('sell_price_per_unit')
                    ->money('USD')
                    ->sortable()
                    ->label('Sell Price'),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => $record->stock_status_color)
                    ->label('Stock'),
                // Tables\Columns\TextColumn::make('low_stock_threshold')
                //     ->sortable()
                //     ->label('Low Stock Threshold')
                //     ->badge()
                //     ->color('gray'),
                // Tables\Columns\TextColumn::make('stock_status')
                //     ->label('Stock Status')
                //     ->badge()
                //     ->color(fn($record) => $record->stock_status_color)
                //     ->formatStateUsing(fn($record) => ucfirst($record->stock_status)),
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
                    ->query(fn (Builder $query): Builder => $query->lowStock()),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->outOfStock()),
                Tables\Filters\Filter::make('active_only')
                    ->label('Active Products Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('custom_threshold')
                    ->label('Custom Low Stock Threshold')
                    ->form([
                        Forms\Components\TextInput::make('threshold')
                            ->numeric()
                            ->label('Threshold Value')
                            ->required()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $threshold = $data['threshold'] ?? 0;
                        if (is_numeric($threshold) && $threshold >= 0) {
                            return $query->where('low_stock_threshold', '>', (int) $threshold);
                        }
                        return $query;
                    }),
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
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Create New Product')
                    ->modalDescription('Add a new product to the system.')
                    ->modalSubmitActionLabel('Create Product'),
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
            RelationManagers\ProductUnitsRelationManager::class,
            RelationManagers\ProductStocksRelationManager::class,
            RelationManagers\PriceHistoriesRelationManager::class,
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
}
