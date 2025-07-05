<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Provider;
use App\Models\CompanyName;
use App\Models\PurchaseInvoice;
use App\Models\ProviderPayment;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Helpers\FormatHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Actions\CreateAction;


class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Provider Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Provider Information')
                    ->schema([
                        Forms\Components\Select::make('company_name_id')
                            ->relationship('companyName', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Company')
                            ->placeholder('Select a company')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Company Name')
                                    ->placeholder('e.g., ABC Corporation, XYZ Ltd.'),
                            ])
                            ->createOptionAction(
                                fn ($action) => $action
                                    ->modalHeading('Create New Company')
                                    ->modalDescription('Add a new company name to the system.')
                                    ->modalSubmitActionLabel('Create Company')
                            ),
                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->label('Provider Name')
                            ->placeholder('e.g., John Doe, Supplier ABC'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->label('Notes')
                            ->placeholder('Additional notes about this provider'),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('companyName.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('purchase_invoices_count')
                    ->counts('purchaseInvoices')
                    ->label('Purchase Invoices')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('purchase_invoices_sum_total_amount')
                    ->label('Total Purchases')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state ?? 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payments_sum_amount')
                    ->label('Total Payments')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state ?? 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(function (Provider $record) {
                        $totalPurchases = $record->purchase_invoices_sum_total_amount ?? 0;
                        $totalPayments = $record->payments_sum_amount ?? 0;
                        return $totalPurchases - $totalPayments;
                    })
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDate($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_name_id')
                    ->label('Filter by Company')
                    ->options(function () {
                        return CompanyName::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All Companies'),
                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query): Builder => $query->whereHas('products')),
                Tables\Filters\Filter::make('has_purchases')
                    ->label('Has Purchases')
                    ->query(fn (Builder $query): Builder => $query->whereHas('purchaseInvoices')),
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Outstanding Balance')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('purchaseInvoices', function ($q) {
                            $q->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM provider_payments WHERE provider_payments.provider_id = providers.id) < (SELECT COALESCE(SUM(total_amount), 0) FROM purchase_invoices WHERE purchase_invoices.provider_id = providers.id)');
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->modalHeading('Edit Provider')
                        ->modalDescription('Update provider information.')
                        ->modalSubmitActionLabel('Update Provider'),
                    Tables\Actions\Action::make('create_invoice')
                        ->label('New Invoice')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Section::make('Invoice Information')
                                ->schema([
                                    Forms\Components\TextInput::make('invoice_number')
                                        ->label('Invoice Number')
                                        ->default(fn () => (\App\Models\PurchaseInvoice::max('id') ?? 0) + 1)
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
                                        ->options(fn () => \App\Models\Branch::pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->label('Branch')
                                        ->default(fn () => auth()->user()->branch_id ?? null)
                                        ->required(),
                                    Forms\Components\Select::make('warehouse_id')
                                        ->options(fn () => \App\Models\Warehouse::pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->label('Warehouse'),
                                    Forms\Components\Select::make('provider_sale_id')
                                        ->label('Provider Sale')
                                        ->options(function ($record) {
                                            if ($record instanceof \App\Models\Provider) {
                                                return $record->providerSales()->pluck('name', 'id')->toArray();
                                            }
                                            return [];
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->default(function ($record) {
                                            if ($record instanceof \App\Models\Provider) {
                                                return $record->providerSales()->orderBy('id')->value('id');
                                            }
                                            return null;
                                        })
                                        ->required()
                                        ->helperText('Select the provider sale who got you the invoice'),
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
                        ->action(function (array $data, Provider $record): void {
                            \DB::transaction(function () use ($data, $record) {
                                $items = $data['items'] ?? [];
                                $total = 0;
                                
                                $invoice = \App\Models\PurchaseInvoice::create([
                                    'provider_id' => $record->id,
                                    'branch_id' => $data['branch_id'] ?? auth()->user()->branch_id,
                                    'warehouse_id' => $data['warehouse_id'] ?? null,
                                    'provider_sale_id' => $data['provider_sale_id'] ?? null,
                                    'invoice_number' => $data['invoice_number'] ?? ((\App\Models\PurchaseInvoice::max('id') ?? 0) + 1),
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
                                            'provider_id' => $record->id,
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
                    Tables\Actions\Action::make('create_payment')
                        ->label('New Payment')
                        ->icon('heroicon-o-credit-card')
                        ->color('warning')
                        ->form([
                            Forms\Components\Section::make('Payment Information')
                                ->schema([
                                    Forms\Components\Select::make('purchase_invoice_id')
                                        ->options(function ($record) {
                                            if ($record instanceof Provider) {
                                                return $record->purchaseInvoices()->pluck('invoice_number', 'id')->toArray();
                                            }
                                            return [];
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
                        ->action(function (array $data, Provider $record): void {
                            $data['provider_id'] = $record->id;
                            ProviderPayment::create($data);
                        })
                        ->modalHeading('Create New Payment')
                        ->modalDescription('Add a new payment for this provider.')
                        ->modalSubmitActionLabel('Create Payment')
                        ->successNotificationTitle('Payment created successfully!'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Create New Provider')
                    ->modalDescription('Add a new provider to the system.')
                    ->modalSubmitActionLabel('Create Provider'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->products()->count() > 0 || $record->purchaseInvoices()->count() > 0) {
                                    throw new \Exception("Cannot delete provider '{$record->name}' because it has products or purchase invoices. Please remove or reassign them first.");
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
            RelationManagers\PurchaseInvoicesRelationManager::class,
            RelationManagers\ProviderPaymentsRelationManager::class,
            RelationManagers\ProviderSalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'view' => Pages\ViewProvider::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'companyName:id,name',
                'products' => function ($query) {
                    $query->select('id', 'provider_id', 'name', 'is_active')
                          ->where('is_active', true);
                }
            ])
            ->withCount([
                'products' => function ($query) {
                    $query->where('is_active', true);
                },
                'purchaseInvoices'
            ])
            ->withSum('purchaseInvoices', 'total_amount')
            ->withSum('payments', 'amount')
            ->select([
                'id', 'name', 'company_name_id', 'notes', 'created_at', 'updated_at'
            ]);
    }

    // Authorization
    public static function canCreate(): bool
    {
        return auth()->user()->can('create providers');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit providers');
    }

    public static function canDelete(Model $record): bool
    {
        if ($record->products()->count() > 0 || $record->purchaseInvoices()->count() > 0) {
            return false;
        }
        return auth()->user()->can('delete providers');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view providers');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view providers');
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\ProviderMonthlyStatsWidget::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'companyName.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name . ' (' . ($record->companyName->name ?? 'No Company') . ')';
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }
}
