<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalariesRelationManager extends RelationManager
{
    protected static string $relationship = 'salaries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pay Period')
                    ->schema([
                        Forms\Components\DatePicker::make('pay_period_start')
                            ->required()
                            ->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('pay_period_end')
                            ->required()
                            ->default(now()->endOfMonth()),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),
                    ])->columns(3),
                
                Forms\Components\Section::make('Earnings')
                    ->schema([
                        Forms\Components\TextInput::make('basic_salary')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('overtime_hours')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('overtime_rate')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('allowances')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('bonuses')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('commissions')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                    ])->columns(2),
                
                Forms\Components\Section::make('Deductions')
                    ->schema([
                        Forms\Components\TextInput::make('social_security')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('health_insurance')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('pension_contribution')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('other_deductions')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                    ])->columns(2),
                
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'check' => 'Check',
                                'mobile_money' => 'Mobile Money',
                            ])
                            ->default('bank_transfer'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'partially_paid' => 'Partially Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft'),
                        Forms\Components\Textarea::make('payment_notes')
                            ->maxLength(500),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payroll_reference')
            ->columns([
                Tables\Columns\TextColumn::make('payroll_reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_period')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('basic_salary')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_deductions')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_remaining')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'paid',
                        'info' => 'partially_paid',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'partially_paid' => 'Partially Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'check' => 'Check',
                        'mobile_money' => 'Mobile Money',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('mark_as_paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->markAsPaid(auth()->user());
                        
                        Notification::make()
                            ->title('Salary Marked as Paid')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'partially_paid'])),
                
                Action::make('partial_payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->maxValue(fn ($record) => $record->amount_remaining),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->processPartialPayment($data['amount'], auth()->user());
                        
                        Notification::make()
                            ->title('Partial Payment Processed')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'partially_paid']) && $record->amount_remaining > 0),
                
                Action::make('generate_payslip')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn ($record): string => route('payslip.download', $record))
                    ->openUrlInNewTab(),
                
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->status = 'cancelled';
                        $record->save();
                        
                        Notification::make()
                            ->title('Salary Record Cancelled')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['draft', 'pending'])),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record): bool => in_array($record->status, ['draft', 'cancelled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('pay_period_start', 'desc');
    }
}