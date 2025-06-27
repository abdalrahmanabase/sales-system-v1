<?php

namespace App\Filament\Resources\ProviderResource\Pages;

use App\Filament\Resources\ProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use App\Models\PurchaseInvoice;
use App\Models\ProviderPayment;
use App\Models\Branch;
use App\Models\Warehouse;
use Filament\Forms;
use App\Models\Provider;
use App\Models\ProviderSale;
use Filament\Notifications\Notification;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ProviderBalanceWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->modalHeading('Edit Provider')
                ->modalDescription('Update provider information.')
                ->modalSubmitActionLabel('Update Provider'),
            Action::make('create_invoice')
                ->label('New Invoice')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => auth()->user()->can('create purchase invoices'))
                ->form([
                    Forms\Components\Section::make('Invoice Information')
                        ->schema([
                            Forms\Components\TextInput::make('invoice_number')
                                ->label('Invoice Number')
                                ->default(fn () => (PurchaseInvoice::max('id') ?? 0) + 1)
                                ->readOnly()
                                ->required(),
                            Forms\Components\DatePicker::make('invoice_date')
                                ->required()
                                ->label('Invoice Date')
                                ->default(now()),
                            Forms\Components\TextInput::make('total_amount')
                                ->numeric()
                                ->label('Total Amount')
                                ->prefix('$')
                                ->placeholder('0.00')
                                ->readOnly()
                                ->helperText('Calculated from items below'),
                        ])->columns(3),
                    Forms\Components\Section::make('Location Information')
                        ->schema([
                            Forms\Components\Select::make('branch_id')
                                ->options(fn () => Branch::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->label('Branch')
                                ->default(fn () => auth()->user()->branch_id ?? null)
                                ->required(),
                        ])->columns(2),
                    Forms\Components\Section::make('Invoice Items')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->label('Invoice Items')
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
                                        })
                                        ->rules([
                                            function ($get) {
                                                return function (string $attribute, $value, $fail) use ($get) {
                                                    if (empty($value) && empty($get('product_id'))) {
                                                        $fail('Either barcode or product must be provided.');
                                                    }
                                                };
                                            }
                                        ]),
                                    Forms\Components\Select::make('product_id')
                                        ->label('Product')
                                        ->options(fn () => \App\Models\Product::pluck('name', 'id')->toArray())
                                        ->searchable()
                                        ->placeholder('Select existing product or scan barcode for new')
                                        ->live()
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
                                        })
                                        ->rules([
                                            function ($get) {
                                                return function (string $attribute, $value, $fail) use ($get) {
                                                    if (empty($value) && empty($get('barcode'))) {
                                                        $fail('Either barcode or product must be provided.');
                                                    }
                                                };
                                            }
                                        ]),
                                    Forms\Components\TextInput::make('product_name')
                                        ->label('Product Name')
                                        ->required()
                                        ->placeholder('Product name')
                                        ->live()
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
                                    Forms\Components\TextInput::make('unit_price')
                                        ->numeric()
                                        ->required()
                                        ->prefix('$')
                                        ->placeholder('0.00')
                                        ->readOnly(fn ($get) => !empty($get('product_id')))
                                        ->rules([
                                            function ($get) {
                                                return function (string $attribute, $value, $fail) use ($get) {
                                                    if (!empty($get('product_id')) && (empty($value) || $value == 0)) {
                                                        $fail('Purchase price cannot be zero for existing products.');
                                                    }
                                                };
                                            }
                                        ]),
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
                ->action(function (array $data): void {
                    \DB::transaction(function () use ($data) {
                        $items = $data['items'] ?? [];
                        $total = 0;
                        
                        $invoice = PurchaseInvoice::create([
                            'provider_id' => $this->record->id,
                            'branch_id' => $data['branch_id'] ?? auth()->user()->branch_id,
                            'warehouse_id' => $data['warehouse_id'] ?? null,
                            'invoice_number' => $data['invoice_number'] ?? ((PurchaseInvoice::max('id') ?? 0) + 1),
                            'invoice_date' => $data['invoice_date'],
                            'total_amount' => 0, // Will be calculated below
                            'notes' => $data['notes'] ?? null,
                            'image_path' => $data['image_path'] ?? null,
                        ]);
                        
                        foreach ($items as $item) {
                            $product = null;
                            
                            // Check if product_id is provided (existing product)
                            if (!empty($item['product_id'])) {
                                $product = \App\Models\Product::find($item['product_id']);
                            }
                            
                            // If no product found by ID, try to find by barcode
                            if (!$product && !empty($item['barcode'])) {
                                $product = \App\Models\Product::where('barcode', $item['barcode'])->first();
                            }
                            
                            // If still no product found, create new one
                            if (!$product) {
                                $product = \App\Models\Product::create([
                                    'name' => $item['product_name'] ?? '',
                                    'barcode' => $item['barcode'] ?? null,
                                    'purchase_price_per_unit' => $item['unit_price'] ?? 0,
                                    'sell_price_per_unit' => $item['sell_price'] ?? 0,
                                    'provider_id' => $this->record->id,
                                    'stock' => 0,
                                    'low_stock_threshold' => 10,
                                    'is_active' => true,
                                ]);
                            } else {
                                // Check if prices changed and record history
                                $oldPurchasePrice = $product->purchase_price_per_unit;
                                $oldSellPrice = $product->sell_price_per_unit;
                                $newPurchasePrice = $item['unit_price'] ?? $oldPurchasePrice;
                                $newSellPrice = $item['sell_price'] ?? $oldSellPrice;
                                
                                if ($newPurchasePrice != $oldPurchasePrice || $newSellPrice != $oldSellPrice) {
                                    // Update product prices
                                    $product->update([
                                        'purchase_price_per_unit' => $newPurchasePrice,
                                        'sell_price_per_unit' => $newSellPrice,
                                    ]);
                                    
                                    // Record price change history
                                    $product->recordPriceChange(
                                        $oldPurchasePrice,
                                        $newPurchasePrice,
                                        $oldSellPrice,
                                        $newSellPrice,
                                        'invoice_update',
                                        "Price updated via invoice #{$invoice->invoice_number}"
                                    );
                                }
                            }
                            
                            // Create invoice item
                            $unitPrice = $item['unit_price'] ?? 0;
                            $cost = ($item['is_bonus'] ?? false) ? 0 : $unitPrice;
                            $invoice->items()->create([
                                'product_id' => $product->id,
                                'quantity' => $item['quantity'] ?? 1,
                                'purchase_price' => $unitPrice,
                                'sell_price' => $item['sell_price'] ?? 0,
                                'is_bonus' => $item['is_bonus'] ?? false,
                            ]);
                            
                            // Add to product stock (always, even if bonus)
                            $product->increment('stock', $item['quantity'] ?? 1);
                            
                            $total += $cost * ($item['quantity'] ?? 1);
                        }
                        
                        // Update invoice total
                        $invoice->update(['total_amount' => $total]);
                    });
                })
                ->requiresConfirmation()
                ->modalDescription(function (array $data) {
                    $items = $data['items'] ?? [];
                    $newProducts = collect($items)->filter(fn($item) => $item['is_new_product'] ?? false);
                    
                    if ($newProducts->count() > 0) {
                        $newProductNames = $newProducts->pluck('product_name')->implode(', ');
                        return "This will create a new purchase invoice. The following new products will be created: {$newProductNames}";
                    }
                    
                    return "This will create a new purchase invoice.";
                })
                ->modalHeading('Create New Purchase Invoice')
                ->modalSubmitActionLabel('Create Invoice')
                ->modalWidth('5xl')
                ->successNotificationTitle('Purchase invoice created successfully!'),
            Action::make('create_payment')
                ->label('New Payment')
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->visible(fn () => auth()->user()->can('create provider payments'))
                ->form([
                    Forms\Components\Section::make('Payment Information')
                        ->schema([
                            Forms\Components\Select::make('purchase_invoice_id')
                                ->options(function () {
                                    return $this->record->purchaseInvoices()->pluck('invoice_number', 'id')->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->label('Purchase Invoice')
                                ->placeholder('Select invoice (optional)')
                                ->helperText('Link this payment to a specific invoice'),
                            Forms\Components\DatePicker::make('payment_date')
                                ->required()
                                ->label('Payment Date')
                                ->default(now()),
                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->numeric()
                                ->label('Payment Amount')
                                ->prefix('$')
                                ->placeholder('0.00'),
                            Forms\Components\Select::make('payment_method')
                                ->options([
                                    'cash' => 'Cash',
                                    'bank_transfer' => 'Bank Transfer',
                                    'check' => 'Check',
                                    'credit_card' => 'Credit Card',
                                    'other' => 'Other',
                                ])
                                ->label('Payment Method')
                                ->placeholder('Select payment method'),
                            Forms\Components\Textarea::make('notes')
                                ->rows(3)
                                ->maxLength(1000)
                                ->label('Notes')
                                ->placeholder('Additional notes about this payment'),
                        ])->columns(2),
                ])
                ->action(function (array $data): void {
                    $data['provider_id'] = $this->record->id;
                    ProviderPayment::create($data);
                })
                ->modalHeading('Create New Payment')
                ->modalDescription('Add a new payment for this provider.')
                ->modalSubmitActionLabel('Create Payment')
                ->successNotificationTitle('Payment created successfully!'),
            // \Filament\Actions\Action::make('create_provider_sale')
            //     ->label('Create Provider Sale')
            //     ->icon('heroicon-o-plus')
            //     ->form([
            //         Forms\Components\TextInput::make('name')
            //             ->required()
            //             ->maxLength(255),
            //         Forms\Components\TextInput::make('phone')
            //             ->required()
            //             ->maxLength(255),
            //         Forms\Components\TextInput::make('phone2')
            //             ->maxLength(255),
            //         Forms\Components\Textarea::make('notes')
            //             ->maxLength(65535),
            //     ])
            //     ->action(function (array $data, $record) {
            //         $data['provider_id'] = $record->id;
            //         ProviderSale::create($data);
            //         Notification::make()
            //             ->title('Provider Sale created successfully!')
            //             ->success()
            //             ->send();
            //     })
            //     ->modalHeading('Create Provider Sale')
            //     ->modalSubmitActionLabel('Create'),
        ];
    }
} 