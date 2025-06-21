<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Category;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public function form(Form $form): Form
    {
        return $form->schema([]); // Subcategories are managed in CategoryResource
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Subcategory Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Subcategories')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('top_level')
                    ->label('Top Level')
                    ->query(fn (Builder $query) => $query->whereNull('parent_id')),
                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query) => $query->whereHas('products')),
                Tables\Filters\Filter::make('has_subcategories')
                    ->label('Has Subcategories')
                    ->query(fn (Builder $query) => $query->whereHas('children')),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view_subcategory')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.categories.edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}
