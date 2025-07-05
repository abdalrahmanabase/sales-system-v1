<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeLoanResource\Pages;
use App\Filament\Resources\EmployeeLoanResource\RelationManagers;
use App\Models\EmployeeLoan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeLoanResource extends Resource
{
    protected static ?string $model = EmployeeLoan::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Employee Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Employee Loans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Loan Information')
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
                        Forms\Components\TextInput::make('loan_reference')
                            ->maxLength(255),
                        Forms\Components\Select::make('loan_type')
                            ->options([
                                'personal' => 'Personal',
                                'advance' => 'Advance',
                                'emergency' => 'Emergency',
                                'housing' => 'Housing',
                                'education' => 'Education',
                                'medical' => 'Medical',
                            ])
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Loan Terms')
                    ->schema([
                        Forms\Components\TextInput::make('loan_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('interest_rate')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                        Forms\Components\TextInput::make('installments_count')
                            ->required()
                            ->numeric()
                            ->default(12),
                        Forms\Components\TextInput::make('monthly_payment')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Status & Approval')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'defaulted' => 'Defaulted',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending'),
                        Forms\Components\TextInput::make('total_paid')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('remaining_balance')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\Select::make('approved_by')
                            ->relationship('approvedBy', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('approved_at'),
                        Forms\Components\Textarea::make('approval_notes')
                            ->maxLength(500),
                    ])->columns(2),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('purpose')
                            ->maxLength(500),
                        Forms\Components\Repeater::make('guarantors')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('phone'),
                                Forms\Components\TextInput::make('relationship'),
                                Forms\Components\TextInput::make('address'),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan_reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('loan_type')
                    ->colors([
                        'primary' => 'personal',
                        'warning' => 'advance',
                        'danger' => 'emergency',
                        'success' => 'housing',
                        'info' => 'education',
                        'secondary' => 'medical',
                    ]),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_payment')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'active',
                        'secondary' => 'completed',
                        'danger' => 'defaulted',
                        'gray' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('remaining_balance')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
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
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'defaulted' => 'Defaulted',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('loan_type')
                    ->options([
                        'personal' => 'Personal',
                        'advance' => 'Advance',
                        'emergency' => 'Emergency',
                        'housing' => 'Housing',
                        'education' => 'Education',
                        'medical' => 'Medical',
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active')->whereHas('payments', function ($q) {
                        $q->where('status', 'overdue');
                    }))
                    ->label('Overdue Loans'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->maxLength(500),
                    ])
                    ->action(function (EmployeeLoan $record, array $data): void {
                        $record->approve(auth()->user(), $data['approval_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Loan Approved')
                            ->success()
                            ->body('Loan has been approved successfully.')
                            ->send();
                    })
                    ->visible(fn (EmployeeLoan $record): bool => $record->status === 'pending'),
                
                Action::make('activate')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (EmployeeLoan $record): void {
                        $record->activate();
                        
                        Notification::make()
                            ->title('Loan Activated')
                            ->success()
                            ->body('Loan has been activated successfully.')
                            ->send();
                    })
                    ->visible(fn (EmployeeLoan $record): bool => $record->status === 'approved'),
                
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (EmployeeLoan $record, array $data): void {
                        $record->cancel($data['reason']);
                        
                        Notification::make()
                            ->title('Loan Cancelled')
                            ->success()
                            ->body('Loan has been cancelled successfully.')
                            ->send();
                    })
                    ->visible(fn (EmployeeLoan $record): bool => in_array($record->status, ['pending', 'approved'])),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (EmployeeLoan $record): bool => in_array($record->status, ['pending', 'cancelled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeLoans::route('/'),
            'create' => Pages\CreateEmployeeLoan::route('/create'),
            'view' => Pages\ViewEmployeeLoan::route('/{record}'),
            'edit' => Pages\EditEmployeeLoan::route('/{record}/edit'),
        ];
    }
}