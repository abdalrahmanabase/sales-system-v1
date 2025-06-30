<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\FormatHelper;

class ProductStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'productStocks';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('warehouse_id')
                ->relationship('warehouse', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->label('Warehouse'),
            Forms\Components\Select::make('branch_id')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->label('Branch'),
            Forms\Components\Select::make('unit_id')
                ->relationship('unit', 'name', function (Builder $query) {
                    return $query->where('is_active', true);
                })
                ->searchable()
                ->preload()
                ->label('Unit')
                ->placeholder('Select unit (optional)'),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required()
                ->label('Quantity')
                ->step(0.0001)
                ->minValue(0),
            Forms\Components\Select::make('source_type')
                ->label('Source Type')
                ->options([
                    'provider' => 'Provider',
                    'sale' => 'Sale',
                    'return' => 'Return',
                    'transfer' => 'Transfer',
                    'adjustment' => 'Adjustment',
                ])
                ->placeholder('Select source type (optional)'),
            Forms\Components\TextInput::make('source_reference')
                ->label('Source Reference')
                ->placeholder('e.g., Invoice #123, Sale #456')
                ->helperText('Reference number or identifier for the source'),
            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->placeholder('Additional notes about this stock entry')
                ->rows(3),
            Forms\Components\DateTimePicker::make('last_updated_at')
                ->label('Last Updated At')
                ->default(now()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('warehouse.name')
                ->sortable()
                ->searchable()
                ->badge()
                ->color('info'),
            Tables\Columns\TextColumn::make('branch.name')
                ->sortable()
                ->searchable()
                ->badge()
                ->color('warning'),
            Tables\Columns\TextColumn::make('unit.name')
                ->label('Unit')
                ->badge()
                ->color('success')
                ->placeholder('Base Unit'),
            Tables\Columns\TextColumn::make('quantity')
                ->sortable()
                ->label('Quantity')
                ->formatStateUsing(fn ($state, $record) => FormatHelper::formatQuantity($state) . ' ' . ($record?->unit?->abbreviation ?? 'units'))
                ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),
            Tables\Columns\TextColumn::make('provider_name')
                ->label('Provider')
                ->formatStateUsing(function ($record) {
                    if (!$record) return FormatHelper::formatText('N/A');
                    if ($record->source_type === 'provider' && $record->source_id) {
                        $provider = \App\Models\Provider::find($record->source_id);
                        return $provider ? FormatHelper::formatText($provider->name) : FormatHelper::formatText('Unknown Provider');
                    }
                    return FormatHelper::formatText('N/A');
                })
                ->placeholder(FormatHelper::formatText('N/A'))
                ->visible(fn ($record) => $record && $record->source_type === 'provider'),
            Tables\Columns\TextColumn::make('source_type')
                ->label('Source')
                ->badge()
                ->icon(fn ($record) => match ($record?->source_type) {
                    'provider' => 'heroicon-o-user-group',
                    'sale' => 'heroicon-o-shopping-cart',
                    'return' => 'heroicon-o-arrow-uturn-left',
                    'transfer' => 'heroicon-o-arrows-right-left',
                    'adjustment' => 'heroicon-o-wrench-screwdriver',
                    default => 'heroicon-o-question-mark-circle',
                })
                ->color(fn (string $state): string => match ($state) {
                    'provider' => 'info',
                    'sale' => 'success',
                    'return' => 'warning',
                    'transfer' => 'primary',
                    'adjustment' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(function ($state, $record) {
                    if (!$record) return FormatHelper::formatText('Manual');
                    if ($state === 'provider' && $record->source_reference) {
                        return FormatHelper::formatText('Provider (Invoice)');
                    }
                    return FormatHelper::formatText(ucfirst($state ?? 'Manual'));
                })
                ->placeholder(FormatHelper::formatText('Manual')),
            Tables\Columns\TextColumn::make('source_reference')
                ->label('Reference')
                ->formatStateUsing(function ($state, $record) {
                    if (!$record) return FormatHelper::formatText('N/A');
                    if ($record->source_type === 'provider' && $state) {
                        if (preg_match('/Invoice #(\\d+)/', $state, $matches)) {
                            $invoiceNumber = $matches[1];
                            $invoice = \App\Models\PurchaseInvoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                return $state;
                            }
                        }
                    }
                    if ($record->source_type === 'sale' && $state) {
                        return FormatHelper::formatText('Sale #' . $state);
                    }
                    if ($record->source_type === 'return' && $state) {
                        return FormatHelper::formatText('Return #' . $state);
                    }
                    if ($record->source_type === 'transfer' && $state) {
                        return FormatHelper::formatText('Transfer #' . $state);
                    }
                    if ($record->source_type === 'adjustment' && $state) {
                        return FormatHelper::formatText('Adjustment: ' . $state);
                    }
                    return $state ? FormatHelper::formatText($state) : FormatHelper::formatText('N/A');
                })
                ->url(function ($record) {
                    if ($record && $record->source_type === 'provider' && $record->source_reference) {
                        if (preg_match('/Invoice #(\\d+)/', $record->source_reference, $matches)) {
                            $invoiceNumber = $matches[1];
                            $invoice = \App\Models\PurchaseInvoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                return \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $invoice->id]);
                            }
                        }
                    }
                    return null;
                })
                ->placeholder(FormatHelper::formatText('N/A'))
                ->limit(30),
            Tables\Columns\TextColumn::make('last_updated_at')
                ->formatStateUsing(fn ($state) => FormatHelper::formatDate($state))
                ->sortable()
                ->label('Last Updated'),
            Tables\Columns\TextColumn::make('stockMovements_count')
                ->counts('stockMovements')
                ->label('Movements')
                ->badge()
                ->color('info'),
            Tables\Columns\TextColumn::make('latest_movement')
                ->label('Latest Movement')
                ->formatStateUsing(function ($record) {
                    if (!$record) return FormatHelper::formatText('No movements');
                    $latestMovement = $record->stockMovements()->latest()->first();
                    if ($latestMovement) {
                        $type = match($latestMovement->movement_type) {
                            'in' => '📥',
                            'out' => '📤',
                            'transfer' => '🔄',
                            'adjustment' => '⚙️',
                            default => '📊'
                        };
                        $quantity = FormatHelper::formatQuantity($latestMovement->quantity);
                        $timeAgo = FormatHelper::formatTimeAgo($latestMovement->created_at);
                        return FormatHelper::formatText($type . ' ' . $quantity . ' (' . $timeAgo . ')');
                    }
                    return FormatHelper::formatText('No movements');
                })
                ->html()
                ->limit(50),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('warehouse_id')
                ->label('Filter by Warehouse')
                ->options(function () {
                    return \App\Models\Warehouse::pluck('name', 'id')->toArray();
                })
                ->placeholder('All Warehouses'),
            Tables\Filters\SelectFilter::make('branch_id')
                ->label('Filter by Branch')
                ->options(function () {
                    return \App\Models\Branch::pluck('name', 'id')->toArray();
                })
                ->placeholder('All Branches'),
            Tables\Filters\SelectFilter::make('unit_id')
                ->label('Filter by Unit')
                ->options(function () {
                    return $this->getOwnerRecord()->productUnits()
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->placeholder('All Units'),
            Tables\Filters\SelectFilter::make('source_type')
                ->label('Filter by Source')
                ->options([
                    'provider' => 'Provider',
                    'sale' => 'Sale',
                    'return' => 'Return',
                    'transfer' => 'Transfer',
                    'adjustment' => 'Adjustment',
                ])
                ->placeholder('All Sources'),
            Tables\Filters\SelectFilter::make('provider_id')
                ->label('Filter by Provider')
                ->options(function () {
                    return \App\Models\Provider::pluck('name', 'id')->toArray();
                })
                ->placeholder('All Providers')
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['values'])) {
                        return $query->where('source_type', 'provider')
                                   ->whereIn('source_id', $data['values']);
                    }
                    return $query;
                }),
            Tables\Filters\Filter::make('in_stock')
                ->label('In Stock Only')
                ->query(fn (Builder $query): Builder => $query->where('quantity', '>', 0)),
            Tables\Filters\Filter::make('out_of_stock')
                ->label('Out of Stock')
                ->query(fn (Builder $query): Builder => $query->where('quantity', '<=', 0)),
            Tables\Filters\Filter::make('low_stock')
                ->label('Low Stock')
                ->query(fn (Builder $query): Builder => $query->where('quantity', '<=', 10)),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->modalHeading('Add Stock Entry')
                ->modalDescription('Add a new stock entry for this product.')
                ->modalSubmitActionLabel('Add Stock')
                ->after(function ($record) {
                    // Record the initial stock movement
                    $record->recordStockMovement(
                        'in',
                        $record->quantity,
                        'Initial stock entry',
                        $record->source_type,
                        $record->source_id,
                        $record->source_reference
                    );
                }),
        ])
        ->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Stock Details')
                    ->modalDescription('View detailed information about this stock entry.')
                    ->modalContent(function ($record) {
                        return view('filament.components.stock-details', [
                            'record' => $record,
                        ]);
                    }),
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Stock Entry')
                    ->modalDescription('Update stock information.')
                    ->modalSubmitActionLabel('Update Stock')
                    ->before(function ($record, array $data) {
                        // Store the old quantity for comparison
                        $record->old_quantity = $record->quantity;
                    })
                    ->after(function ($record, array $data) {
                        $oldQuantity = $record->old_quantity ?? 0;
                        $newQuantity = $data['quantity'] ?? 0;
                        $quantityChange = $newQuantity - $oldQuantity;

                        if ($quantityChange != 0) {
                            $movementType = $quantityChange > 0 ? 'in' : 'out';
                            $notes = $quantityChange > 0 ? 'Stock increased' : 'Stock decreased';
                            
                            $record->recordStockMovement(
                                $movementType,
                                abs($quantityChange),
                                $notes,
                                $record->source_type,
                                $record->source_id,
                                $record->source_reference
                            );
                        }
                    }),
                Tables\Actions\Action::make('view_movements')
                    ->label('View Movements')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->modalHeading('Stock Movements')
                    ->modalDescription('View all stock movements for this entry.')
                    ->modalContent(function ($record) {
                        $movements = $record->stockMovements()->orderBy('created_at', 'desc')->get();
                        return view('filament.components.stock-movements', [
                            'movements' => $movements,
                            'record' => $record,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        if ($record->stockMovements()->count() > 0) {
                            throw new \Exception('Cannot delete stock entry with movements. Please delete movements first.');
                        }
                    })
                    ->after(function ($record) {
                        // Record stock removal movement
                        if ($record->quantity > 0) {
                            $record->recordStockMovement(
                                'out',
                                $record->quantity,
                                'Stock entry deleted',
                                'adjustment',
                                null,
                                'Manual deletion'
                            );
                        }
                    }),
            ]),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        foreach ($records as $record) {
                            if ($record->stockMovements()->count() > 0) {
                                throw new \Exception("Cannot delete stock entry '{$record->id}' with movements. Please delete movements first.");
                            }
                        }
                    })
                    ->after(function ($records) {
                        foreach ($records as $record) {
                            // Record stock removal movement for each deleted record
                            if ($record->quantity > 0) {
                                $record->recordStockMovement(
                                    'out',
                                    $record->quantity,
                                    'Stock entry deleted (bulk action)',
                                    'adjustment',
                                    null,
                                    'Bulk deletion'
                                );
                            }
                        }
                    }),
            ]),
        ])
        ->defaultSort('last_updated_at', 'desc')
        ->paginated([10, 25, 50, 100]);
    }
} 