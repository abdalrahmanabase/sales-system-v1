<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Employee Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Employee Information')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Personal Information')
                            ->schema([
                                Forms\Components\Section::make('Basic Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('employee_id')
                                            ->label('Employee ID')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('first_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('middle_name')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_name')
                                            ->maxLength(255),
                                        Forms\Components\DatePicker::make('date_of_birth'),
                                        Forms\Components\Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                                'other' => 'Other',
                                            ]),
                                    ])->columns(2),
                                
                                Forms\Components\Section::make('Identification')
                                    ->schema([
                                        Forms\Components\TextInput::make('national_id')
                                            ->label('National ID')
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('social_security_number')
                                            ->label('Social Security Number')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('passport_number')
                                            ->label('Passport Number')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('nationality')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('marital_status')
                                            ->maxLength(50),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Contact Information')
                            ->schema([
                                Forms\Components\Section::make('Contact Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('address')
                                            ->maxLength(500),
                                        Forms\Components\TextInput::make('city')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('state')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('postal_code')
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('country')
                                            ->maxLength(100),
                                    ])->columns(2),
                                
                                Forms\Components\Section::make('Emergency Contact')
                                    ->schema([
                                        Forms\Components\TextInput::make('emergency_contact_name')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('emergency_contact_phone')
                                            ->tel()
                                            ->maxLength(20),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Employment Details')
                            ->schema([
                                Forms\Components\Section::make('Position Information')
                                    ->schema([
                                        Forms\Components\Select::make('branch_id')
                                            ->relationship('branch', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\TextInput::make('job_title')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('department')
                                            ->maxLength(255),
                                        Forms\Components\Select::make('employee_type')
                                            ->options([
                                                'full_time' => 'Full Time',
                                                'part_time' => 'Part Time',
                                                'contract' => 'Contract',
                                                'intern' => 'Intern',
                                            ])
                                            ->default('full_time'),
                                        Forms\Components\DatePicker::make('hire_date'),
                                        Forms\Components\DatePicker::make('probation_end_date'),
                                        Forms\Components\DatePicker::make('contract_end_date'),
                                    ])->columns(2),
                                
                                Forms\Components\Section::make('Employment Status')
                                    ->schema([
                                        Forms\Components\Select::make('employment_status')
                                            ->options([
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                                'terminated' => 'Terminated',
                                                'resigned' => 'Resigned',
                                            ])
                                            ->default('active'),
                                        Forms\Components\DatePicker::make('termination_date'),
                                        Forms\Components\Textarea::make('termination_reason')
                                            ->maxLength(500),
                                        Forms\Components\Toggle::make('is_active')
                                            ->default(true),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Compensation')
                            ->schema([
                                Forms\Components\Section::make('Salary Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('salary')
                                            ->numeric()
                                            ->prefix('$')
                                            ->maxValue(999999.99),
                                        Forms\Components\TextInput::make('hourly_rate')
                                            ->numeric()
                                            ->prefix('$')
                                            ->maxValue(999.99),
                                        Forms\Components\TextInput::make('working_hours_per_week')
                                            ->numeric()
                                            ->default(40)
                                            ->minValue(1)
                                            ->maxValue(80),
                                        Forms\Components\TextInput::make('tax_rate')
                                            ->numeric()
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100),
                                    ])->columns(2),
                                
                                Forms\Components\Section::make('Banking Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_account_number')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('bank_name')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('bank_routing_number')
                                            ->maxLength(50),
                                        Forms\Components\TextInput::make('tax_id')
                                            ->label('Tax ID')
                                            ->maxLength(50),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Additional Information')
                            ->schema([
                                Forms\Components\FileUpload::make('profile_picture')
                                    ->image()
                                    ->imageEditor(),
                                Forms\Components\Textarea::make('benefits')
                                    ->maxLength(1000),
                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(1000),
                                Forms\Components\Textarea::make('contact_info')
                                    ->maxLength(1000),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_picture')
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('employee_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('employee_type')
                    ->colors([
                        'primary' => 'full_time',
                        'warning' => 'part_time',
                        'info' => 'contract',
                        'success' => 'intern',
                    ]),
                Tables\Columns\BadgeColumn::make('employment_status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'terminated',
                        'secondary' => 'resigned',
                    ]),
                Tables\Columns\TextColumn::make('salary')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hire_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'terminated' => 'Terminated',
                        'resigned' => 'Resigned',
                    ]),
                Tables\Filters\SelectFilter::make('employee_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract' => 'Contract',
                        'intern' => 'Intern',
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
                Tables\Filters\Filter::make('is_active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->label('Active Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('resign')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resign Employee')
                    ->modalDescription('Are you sure you want to resign this employee?')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Resignation Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Employee $record, array $data): void {
                        $record->resign($data['reason'], auth()->user());
                        
                        Notification::make()
                            ->title('Employee Resigned')
                            ->success()
                            ->body('Employee has been resigned successfully.')
                            ->send();
                    })
                    ->visible(fn (Employee $record): bool => $record->employment_status === 'active'),
                
                Action::make('terminate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Terminate Employee')
                    ->modalDescription('Are you sure you want to terminate this employee?')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Termination Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Employee $record, array $data): void {
                        $record->terminate($data['reason'], auth()->user());
                        
                        Notification::make()
                            ->title('Employee Terminated')
                            ->success()
                            ->body('Employee has been terminated successfully.')
                            ->send();
                    })
                    ->visible(fn (Employee $record): bool => $record->employment_status === 'active'),
                
                Action::make('reactivate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Employee $record): void {
                        $record->reactivate(auth()->user());
                        
                        Notification::make()
                            ->title('Employee Reactivated')
                            ->success()
                            ->body('Employee has been reactivated successfully.')
                            ->send();
                    })
                    ->visible(fn (Employee $record): bool => in_array($record->employment_status, ['resigned', 'terminated'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Employee Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Personal Information')
                            ->schema([
                                Infolists\Components\Section::make('Basic Information')
                                    ->schema([
                                        Infolists\Components\ImageEntry::make('profile_picture')
                                            ->circular()
                                            ->size(100),
                                        Infolists\Components\TextEntry::make('employee_id'),
                                        Infolists\Components\TextEntry::make('full_name'),
                                        Infolists\Components\TextEntry::make('date_of_birth')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('age'),
                                        Infolists\Components\TextEntry::make('gender'),
                                        Infolists\Components\TextEntry::make('nationality'),
                                        Infolists\Components\TextEntry::make('marital_status'),
                                    ])->columns(2),
                                
                                Infolists\Components\Section::make('Contact Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('phone'),
                                        Infolists\Components\TextEntry::make('email'),
                                        Infolists\Components\TextEntry::make('address'),
                                        Infolists\Components\TextEntry::make('emergency_contact_name'),
                                        Infolists\Components\TextEntry::make('emergency_contact_phone'),
                                    ])->columns(2),
                            ]),
                        
                        Infolists\Components\Tabs\Tab::make('Employment')
                            ->schema([
                                Infolists\Components\Section::make('Position Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('job_title'),
                                        Infolists\Components\TextEntry::make('department'),
                                        Infolists\Components\TextEntry::make('branch.name'),
                                        Infolists\Components\TextEntry::make('employee_type'),
                                        Infolists\Components\TextEntry::make('employment_status'),
                                        Infolists\Components\TextEntry::make('hire_date')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('years_of_service'),
                                    ])->columns(2),
                                
                                Infolists\Components\Section::make('Compensation')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('salary')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('hourly_rate')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('working_hours_per_week'),
                                        Infolists\Components\TextEntry::make('tax_rate')
                                            ->suffix('%'),
                                    ])->columns(2),
                            ]),
                        
                        Infolists\Components\Tabs\Tab::make('Financial Summary')
                            ->schema([
                                Infolists\Components\Section::make('Loan Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('total_active_loans')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('monthly_loan_deductions')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('has_outstanding_loans')
                                            ->boolean(),
                                    ])->columns(3),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendanceRelationManager::class,
            RelationManagers\LoansRelationManager::class,
            RelationManagers\SalariesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}