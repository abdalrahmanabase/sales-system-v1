<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeSalaryResource\Pages;
use App\Filament\Resources\EmployeeSalaryResource\RelationManagers;
use App\Models\EmployeeSalary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeSalaryResource extends Resource
{
    protected static ?string $model = EmployeeSalary::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Employee Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Salaries & Payroll';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee & Period')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->employee_id . ')')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('payroll_reference')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('pay_period_start')
                            ->required()
                            ->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('pay_period_end')
                            ->required()
                            ->default(now()->endOfMonth()),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),
                    ])->columns(2),
                
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
                        Forms\Components\TextInput::make('overtime_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
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
                        Forms\Components\TextInput::make('gross_salary')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Deductions')
                    ->schema([
                        Forms\Components\TextInput::make('income_tax')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
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
                        Forms\Components\TextInput::make('loan_deductions')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->readOnly(),
                        Forms\Components\TextInput::make('other_deductions')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('total_deductions')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                        Forms\Components\TextInput::make('net_salary')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('amount_remaining')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
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
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('processed_by')
                            ->relationship('processedBy', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('processed_at'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Breakdown Details')
                    ->schema([
                        Forms\Components\KeyValue::make('deduction_breakdown')
                            ->label('Deduction Breakdown')
                            ->keyLabel('Deduction Type')
                            ->valueLabel('Amount'),
                        Forms\Components\KeyValue::make('allowance_breakdown')
                            ->label('Allowance Breakdown')
                            ->keyLabel('Allowance Type')
                            ->valueLabel('Amount'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payroll_reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.employee_id')
                    ->label('Employee ID')
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
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
                Tables\Filters\Filter::make('pay_period')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('pay_period_start', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('pay_period_end', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending')->where('payment_date', '<', now()))
                    ->label('Overdue Payments'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('mark_as_paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (EmployeeSalary $record): void {
                        $record->markAsPaid(auth()->user());
                        
                        Notification::make()
                            ->title('Salary Marked as Paid')
                            ->success()
                            ->body('Salary has been marked as paid successfully.')
                            ->send();
                    })
                    ->visible(fn (EmployeeSalary $record): bool => in_array($record->status, ['pending', 'partially_paid'])),
                
                Action::make('partial_payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->maxValue(fn (EmployeeSalary $record) => $record->amount_remaining),
                    ])
                    ->action(function (EmployeeSalary $record, array $data): void {
                        $record->processPartialPayment($data['amount'], auth()->user());
                        
                        Notification::make()
                            ->title('Partial Payment Processed')
                            ->success()
                            ->body('Partial payment has been processed successfully.')
                            ->send();
                    })
                    ->visible(fn (EmployeeSalary $record): bool => in_array($record->status, ['pending', 'partially_paid']) && $record->amount_remaining > 0),
                
                Action::make('generate_payslip')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (EmployeeSalary $record): string => route('payslip.download', $record))
                    ->openUrlInNewTab(),
                
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EmployeeSalary $record): void {
                        $record->status = 'cancelled';
                        $record->save();
                        
                        Notification::make()
                            ->title('Salary Record Cancelled')
                            ->success()
                            ->body('Salary record has been cancelled successfully.')
                            ->send();
                    })
                    ->visible(fn (EmployeeSalary $record): bool => in_array($record->status, ['draft', 'pending'])),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (EmployeeSalary $record): bool => in_array($record->status, ['draft', 'cancelled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'partially_paid'])) {
                                    $record->markAsPaid(auth()->user());
                                }
                            }
                            
                            Notification::make()
                                ->title('Salaries Updated')
                                ->success()
                                ->body('Selected salaries have been marked as paid.')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeSalaries::route('/'),
            'create' => Pages\CreateEmployeeSalary::route('/create'),
            'view' => Pages\ViewEmployeeSalary::route('/{record}'),
            'edit' => Pages\EditEmployeeSalary::route('/{record}/edit'),
        ];
    }
}