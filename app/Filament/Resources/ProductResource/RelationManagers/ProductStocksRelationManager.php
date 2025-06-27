<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'productStocks';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('warehouse_id')
                ->relationship('warehouse', 'name')
                ->required(),
            Forms\Components\Select::make('branch_id')
                ->relationship('branch', 'name')
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required(),
            Forms\Components\DateTimePicker::make('last_updated_at')
                ->label('Last Updated At'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('warehouse.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('branch.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('quantity')->sortable(),
            Tables\Columns\TextColumn::make('last_updated_at')->dateTime()->sortable(),
        ])
        ->filters([])
        ->headerActions([
            Tables\Actions\CreateAction::make(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }
} 