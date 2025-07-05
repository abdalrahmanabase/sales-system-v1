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

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\TextInput::make('monthly_payment')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Textarea::make('purpose')
                    ->maxLength(500),
                Forms\Components\Repeater::make('guarantors')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('phone'),
                        Forms\Components\TextInput::make('relationship'),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('loan_reference')
            ->columns([
                Tables\Columns\TextColumn::make('loan_reference')
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
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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
                    ->action(function ($record, array $data): void {
                        $record->approve(auth()->user(), $data['approval_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Loan Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'pending'),
                
                Action::make('activate')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->activate();
                        
                        Notification::make()
                            ->title('Loan Activated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'approved'),
                
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
                    ->action(function ($record, array $data): void {
                        $record->cancel($data['reason']);
                        
                        Notification::make()
                            ->title('Loan Cancelled')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'approved'])),
                
                Action::make('view_schedule')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->modalHeading('Payment Schedule')
                    ->modalContent(function ($record) {
                        $schedule = $record->createPaymentSchedule();
                        return view('filament.modals.payment-schedule', compact('schedule'));
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'cancelled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}