<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Filament\Components\GlobalBarcodeScanner;
use App\Helpers\FormatHelper;

class PurchaseInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseInvoices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->maxLength(255)
                            ->label('Invoice Number')
                            ->placeholder('e.g., INV-2024-001'),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->required()
                            ->label('Invoice Date')
                            ->default(now()),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->label('Total Amount')
                            ->prefix('$')
                            ->placeholder('0.00'),
                    ])->columns(3),
                Forms\Components\Section::make('Location Information')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Warehouse')
                            ->placeholder('Select a warehouse (optional)'),
                    ])->columns(2),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->label('Invoice Image')
                            ->helperText('Upload a copy of the invoice'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->label('Notes')
                            ->placeholder('Additional notes about this invoice'),
                    ]),
                Forms\Components\Section::make('Invoice Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                GlobalBarcodeScanner::make('barcode')
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                            $product = Product::findByBarcode($state);
                                            if ($product) {
                                                $set('product_id', $product->id);
                                                $set('product_name', $product->name);
                                                $set('purchase_price', $product->purchase_price_per_unit);
                                            } else {
                                                // Clear fields if product not found
                                                $set('product_id', null);
                                                $set('product_name', '');
                                                $set('purchase_price', '');
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('product_name')
                                    ->label('Product Name')
                                    ->placeholder('Product name (auto-filled from barcode)')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn () => \App\Models\Product::pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->placeholder('Select existing product or scan barcode for new')
                                    ->afterStateHydrated(function ($state, $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('barcode', $product->barcode);
                                                $set('product_name', $product->name);
                                                $set('unit_price', $product->purchase_price_per_unit);
                                                $set('sell_price', $product->sell_price_per_unit);
                                                $set('is_new_product', false);
                                            }
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('barcode', $product->barcode);
                                                $set('product_name', $product->name);
                                                $set('unit_price', $product->purchase_price_per_unit);
                                                $set('sell_price', $product->sell_price_per_unit);
                                                $set('is_new_product', false);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->label('Quantity')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('purchase_price')
                                    ->numeric()
                                    ->required()
                                    ->label('Purchase Price per Unit')
                                    ->prefix('$')
                                    ->placeholder('0.00'),
                                Forms\Components\Toggle::make('is_bonus')
                                    ->label('Bonus?')
                                    ->helperText('Mark as bonus item (not counted in total)'),
                            ])
                            ->columns(2)
                            ->label('Invoice Items')
                            ->createItemButtonLabel('Add Item')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? 'New Item'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDate($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->badge()
                    ->color('success')
                    ->placeholder('No Branch'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->badge()
                    ->color('warning')
                    ->placeholder('No Warehouse'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->sortable()
                    ->badge(),
                
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(function ($record) {
                        return $record->balance;
                    })
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filter by Branch')
                    ->options(function () {
                        return Branch::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Branches'),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Filter by Warehouse')
                    ->options(function () {
                        return Warehouse::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Warehouses'),
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Outstanding Balance')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('payments', function ($q) {
                            $q->havingRaw('SUM(amount) < total_amount');
                        })->orWhereDoesntHave('payments');
                    }),
                Tables\Filters\Filter::make('fully_paid')
                    ->label('Fully Paid')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('payments', function ($q) {
                            $q->havingRaw('SUM(amount) >= total_amount');
                        });
                    }),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Edit Purchase Invoice')
                    ->form([
                        Forms\Components\Section::make('Invoice Information')
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->readOnly()
                                    ->required(),
                                Forms\Components\DatePicker::make('invoice_date')
                                    ->required()
                                    ->label('Invoice Date'),
                                Forms\Components\TextInput::make('total_amount')
                                    ->numeric()
                                    ->label('Total Amount')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->helperText('Calculated from items below'),
                            ])->columns(3),
                        Forms\Components\Section::make('Location Information')
                            ->schema([
                                Forms\Components\Select::make('warehouse_id')
                                    ->relationship('warehouse', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->label('Warehouse')
                                    ->placeholder('Select a warehouse (optional)'),
                            ])->columns(2),
                        Forms\Components\Section::make('Invoice Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->label('Invoice Items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('barcode')
                                            ->label('Barcode')
                                            ->placeholder('Scan or enter barcode')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                if ($state) {
                                                    $product = \App\Models\Product::where('barcode', $state)->first();
                                                    if ($product) {
                                                        $set('product_id', $product->id);
                                                        $set('product_name', $product->name);
                                                        $set('unit_price', $product->purchase_price_per_unit);
                                                        $set('sell_price', $product->sell_price_per_unit);
                                                        $set('is_new_product', false);
                                                    } else {
                                                        $set('product_id', null);
                                                        $set('product_name', '');
                                                        $set('unit_price', '');
                                                        $set('sell_price', '');
                                                        $set('is_new_product', true);
                                                    }
                                                }
                                            }),
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->options(fn () => \App\Models\Product::pluck('name', 'id')->toArray())
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->placeholder('Select existing product or scan barcode for new')
                                            ->afterStateHydrated(function ($state, $set) {
                                                if ($state) {
                                                    $product = \App\Models\Product::find($state);
                                                    if ($product) {
                                                        $set('barcode', $product->barcode);
                                                        $set('product_name', $product->name);
                                                        $set('unit_price', $product->purchase_price_per_unit);
                                                        $set('sell_price', $product->sell_price_per_unit);
                                                        $set('is_new_product', false);
                                                    }
                                                }
                                            })
                                            ->afterStateUpdated(function ($state, $set) {
                                                if ($state) {
                                                    $product = \App\Models\Product::find($state);
                                                    if ($product) {
                                                        $set('barcode', $product->barcode);
                                                        $set('product_name', $product->name);
                                                        $set('unit_price', $product->purchase_price_per_unit);
                                                        $set('sell_price', $product->sell_price_per_unit);
                                                        $set('is_new_product', false);
                                                    }
                                                }
                                            }),
                                        Forms\Components\TextInput::make('product_name')
                                            ->label('Product Name')
                                            ->required()
                                            ->placeholder('Product name')
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                if ($state && !$get('product_id')) {
                                                    $set('is_new_product', true);
                                                }
                                            })
                                            ->helperText(function ($get) {
                                                if ($get('is_new_product')) {
                                                    return 'This will create a new product in the system.';
                                                }
                                                return 'Product name (auto-filled when selecting existing product)';
                                            }),
                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->default(1),
                                        Forms\Components\TextInput::make('purchase_price')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->placeholder('0.00'),
                                        Forms\Components\TextInput::make('sell_price')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->placeholder('0.00'),
                                        Forms\Components\Toggle::make('is_bonus')
                                            ->label('Bonus Item')
                                            ->helperText('If checked, cost will be zero but purchase price is still recorded for tracking')
                                            ->default(false),
                                        Forms\Components\Hidden::make('is_new_product')
                                            ->default(false),
                                    ])
                                    ->defaultItems(1)
                                    ->createItemButtonLabel('Add Item')
                                    ->columns(3)
                                    ->itemLabel(fn (array $state): ?string => 
                                        ($state['product_name'] ?? 'New Item') . 
                                        ($state['is_new_product'] ?? false ? ' (NEW)' : '')
                                    ),
                            ]),
                        Forms\Components\Section::make('Additional Information')
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->image()
                                    ->label('Invoice Image')
                                    ->helperText('Upload a copy of the invoice'),
                                Forms\Components\Textarea::make('notes')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->label('Notes')
                                    ->placeholder('Additional notes about this invoice'),
                            ]),
                    ])
                    ->mountUsing(function ($form, $record) {
                        // Load items data with proper relationship loading
                        $items = $record->items()->with('product')->get()->map(function($item) {
                            $product = $item->product;
                            return [
                                'barcode' => $product?->barcode ?? '',
                                'product_id' => $item->product_id,
                                'product_name' => $product?->name ?? '',
                                'quantity' => $item->quantity,
                                'unit_price' => $product?->purchase_price_per_unit ?? $item->purchase_price,
                                'sell_price' => $product?->sell_price_per_unit ?? $item->sell_price,
                                'is_bonus' => $item->is_bonus,
                                'is_new_product' => false,
                            ];
                        })->toArray();
                        
                        // Fill the entire form including items
                        $form->fill([
                            'invoice_number' => $record->invoice_number,
                            'invoice_date' => $record->invoice_date,
                            'total_amount' => $record->total_amount,
                            'warehouse_id' => $record->warehouse_id,
                            'image_path' => $record->image_path,
                            'notes' => $record->notes,
                            'items' => $items,
                        ]);
                    })
                    ->action(function (array $data, $record) {
                        \DB::transaction(function () use ($data, $record) {
                            $items = $data['items'] ?? [];
                            $total = 0;
                            $record->update([
                                'warehouse_id' => $data['warehouse_id'] ?? null,
                                'invoice_date' => $data['invoice_date'],
                                'notes' => $data['notes'] ?? null,
                                'image_path' => $data['image_path'] ?? null,
                            ]);
                            // Delete all old items
                            $record->items()->delete();
                            foreach ($items as $item) {
                                $product = null;
                                if (!empty($item['product_id'])) {
                                    $product = \App\Models\Product::find($item['product_id']);
                                }
                                if (!$product && !empty($item['barcode'])) {
                                    $product = \App\Models\Product::where('barcode', $item['barcode'])->first();
                                }
                                if (!$product) {
                                    $product = \App\Models\Product::create([
                                        'name' => $item['product_name'] ?? '',
                                        'barcode' => $item['barcode'] ?? null,
                                        'purchase_price_per_unit' => $item['unit_price'] ?? 0,
                                        'sell_price_per_unit' => $item['sell_price'] ?? 0,
                                        'provider_id' => $record->provider_id,
                                        'stock' => 0,
                                        'low_stock_threshold' => 10,
                                        'is_active' => true,
                                    ]);
                                } else {
                                    $oldPurchasePrice = $product->purchase_price_per_unit;
                                    $oldSellPrice = $product->sell_price_per_unit;
                                    $newPurchasePrice = $item['unit_price'] ?? $oldPurchasePrice;
                                    $newSellPrice = $item['sell_price'] ?? $oldSellPrice;
                                    if ($newPurchasePrice != $oldPurchasePrice || $newSellPrice != $oldSellPrice) {
                                        $product->update([
                                            'purchase_price_per_unit' => $newPurchasePrice,
                                            'sell_price_per_unit' => $newSellPrice,
                                        ]);
                                        $product->recordPriceChange(
                                            $oldPurchasePrice,
                                            $newPurchasePrice,
                                            $oldSellPrice,
                                            $newSellPrice,
                                            'invoice_update',
                                            "Price updated via invoice #{$record->invoice_number}"
                                        );
                                    }
                                }
                                $unitPrice = $item['unit_price'] ?? 0;
                                $cost = ($item['is_bonus'] ?? false) ? 0 : $unitPrice;
                                $record->items()->create([
                                    'product_id' => $product->id,
                                    'quantity' => $item['quantity'] ?? 1,
                                    'purchase_price' => $unitPrice,
                                    'sell_price' => $item['sell_price'] ?? 0,
                                    'is_bonus' => $item['is_bonus'] ?? false,
                                ]);
                                // Always increment product stock by the item quantity
                                $product->increment('stock', $item['quantity'] ?? 1);
                                // Add to total only if not bonus
                                if (!($item['is_bonus'] ?? false)) {
                                    $total += ($item['quantity'] ?? 1) * $unitPrice;
                                }
                            }
                            // Update invoice total
                            $record->update(['total_amount' => $total]);
                        });
                    })
                    ->modalSubmitActionLabel('Save Changes'),
                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Purchase Invoice')
                    ->modalDescription('Are you sure you want to delete this purchase invoice? This action cannot be undone.')
                    ->action(function ($record) {
                        if ($record->items()->count() > 0) {
                            throw new \Exception("Cannot delete invoice '{$record->invoice_number}' because it has items. Please remove the items first.");
                        }
                        $record->delete();
                    })
                    ->modalSubmitActionLabel('Delete'),
                Tables\Actions\Action::make('view_items')
                    ->label('View Items')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Invoice Items')
                    ->modalContent(function ($record) {
                        $items = $record->items()->with('product')->get();
                        return view('filament.components.invoice-items-table', [
                            'items' => $items,
                            'invoice' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->items()->count() > 0) {
                                    throw new \Exception("Cannot delete invoice '{$record->invoice_number}' because it has items. Please remove the items first.");
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
} 