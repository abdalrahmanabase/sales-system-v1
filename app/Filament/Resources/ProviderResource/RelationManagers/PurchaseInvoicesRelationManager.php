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
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Branch')
                            ->placeholder('Select a branch (optional)')
                            ->default(function () {
                                // For now, we'll let users select the branch manually
                                // You can implement your own logic for default branch assignment
                                return null;
                            }),
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
                                    ->options(function () {
                                        return Product::where('is_active', true)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select product or scan barcode')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('purchase_price', $product->purchase_price_per_unit);
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
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
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
                    ->money('USD')
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
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
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Set the provider_id to the current provider
                        $data['provider_id'] = $this->getOwnerRecord()->id;
                        
                        // Process items to handle barcode-based product creation
                        if (isset($data['items'])) {
                            foreach ($data['items'] as &$item) {
                                if (isset($item['barcode']) && !empty($item['barcode'])) {
                                    // Check if product exists by barcode
                                    $product = Product::findByBarcode($item['barcode']);
                                    
                                    if (!$product) {
                                        // Create new product from barcode
                                        $product = Product::create([
                                            'barcode' => $item['barcode'],
                                            'name' => $item['product_name'] ?? 'Product from Barcode: ' . $item['barcode'],
                                            'purchase_price_per_unit' => $item['purchase_price'] ?? 0,
                                            'sell_price_per_unit' => ($item['purchase_price'] ?? 0) * 1.2, // 20% markup
                                            'provider_id' => $this->getOwnerRecord()->id,
                                            'is_active' => true,
                                        ]);
                                    }
                                    
                                    // Set the product_id for the invoice item
                                    $item['product_id'] = $product->id;
                                }
                                
                                // Remove temporary fields
                                unset($item['barcode'], $item['product_name']);
                            }
                        }
                        
                        return $data;
                    })
                    ->modalHeading('Create New Purchase Invoice')
                    ->modalDescription('Add a new purchase invoice for this provider.')
                    ->modalSubmitActionLabel('Create Invoice'),
            ])
            ->actions([
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
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Purchase Invoice')
                    ->modalDescription('Update purchase invoice information.')
                    ->modalSubmitActionLabel('Update Invoice'),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        if ($record->items()->count() > 0) {
                            throw new \Exception("Cannot delete invoice '{$record->invoice_number}' because it has items. Please remove the items first.");
                        }
                    }),
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