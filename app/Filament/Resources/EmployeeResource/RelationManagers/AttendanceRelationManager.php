<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendance';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('attendance_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TimePicker::make('scheduled_in_time')
                    ->default('09:00'),
                Forms\Components\TimePicker::make('scheduled_out_time')
                    ->default('17:00'),
                Forms\Components\TimePicker::make('check_in_time'),
                Forms\Components\TimePicker::make('check_out_time'),
                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                    ])
                    ->default('present'),
                Forms\Components\TextInput::make('regular_hours')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('overtime_hours')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('break_hours')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_hours')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_holiday')
                    ->default(false),
                Forms\Components\TextInput::make('location'),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('attendance_date')
            ->columns([
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_time')
                    ->time()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                    ]),
                Tables\Columns\TextColumn::make('regular_hours')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_hours')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_holiday')
                    ->boolean(),
                Tables\Columns\TextColumn::make('location')
                    ->limit(20),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                    ]),
                Tables\Filters\Filter::make('is_holiday')
                    ->query(fn (Builder $query): Builder => $query->where('is_holiday', true))
                    ->label('Holidays Only'),
                Tables\Filters\Filter::make('overtime')
                    ->query(fn (Builder $query): Builder => $query->where('overtime_hours', '>', 0))
                    ->label('With Overtime'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('attendance_date', 'desc');
    }
}