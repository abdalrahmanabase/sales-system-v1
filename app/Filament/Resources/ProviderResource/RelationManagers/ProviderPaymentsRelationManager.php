<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseInvoice;
use App\Helpers\FormatHelper;

class ProviderPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('purchase_invoice_id')
                            ->options(function ($record) {
                                // $record is the Provider in the context of the relation manager
                                if ($record && method_exists($record, 'purchaseInvoices')) {
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDate($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('purchaseInvoice.invoice_number')
                    ->label('Invoice')
                    ->badge()
                    ->color('warning')
                    ->placeholder('No Invoice'),
                Tables\Columns\TextColumn::make('purchaseInvoice.total_amount')
                    ->label('Invoice Amount')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->placeholder('No notes'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'check' => 'Check',
                        'credit_card' => 'Credit Card',
                        'other' => 'Other',
                    ])
                    ->label('Payment Method'),
                Tables\Filters\Filter::make('has_invoice')
                    ->label('Linked to Invoice')
                    ->query(fn (Builder $query) => $query->whereNotNull('purchase_invoice_id')),
                Tables\Filters\Filter::make('no_invoice')
                    ->label('No Invoice Link')
                    ->query(fn (Builder $query) => $query->whereNull('purchase_invoice_id')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Set the provider_id to the current provider
                        $data['provider_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->modalHeading('Create New Payment')
                    ->modalDescription('Add a new payment for this provider.')
                    ->modalSubmitActionLabel('Create Payment'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Payment')
                    ->modalDescription('Update payment information.')
                    ->modalSubmitActionLabel('Update Payment'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }
} 