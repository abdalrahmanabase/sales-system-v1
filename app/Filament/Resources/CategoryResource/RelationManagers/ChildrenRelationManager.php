<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Category;
use App\Helpers\FormatHelper;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';
    protected static ?string $title = 'Subcategories';

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
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('full_path')
                    ->label('Full Path')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Subcategories')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state))
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('has_products')
                    ->label('Has Products')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->products()->count() > 0),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query) => $query->whereHas('products')),
                Tables\Filters\Filter::make('no_products')
                    ->label('No Products')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('products')),
                Tables\Filters\Filter::make('has_subcategories')
                    ->label('Has Subcategories')
                    ->query(fn (Builder $query) => $query->whereHas('children')),
                Tables\Filters\Filter::make('no_subcategories')
                    ->label('No Subcategories')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('children')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_subcategory')
                    ->label('Add Subcategory')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => route('filament.admin.resources.categories.create', ['parent_id' => $this->getOwnerRecord()->id]))
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_subcategory')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.categories.view', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('edit_subcategory')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.categories.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view_products')
                    ->label('View Products')
                    ->icon('heroicon-o-cube')
                    ->url(fn ($record) => route('filament.admin.resources.categories.view', ['record' => $record]))
                    ->visible(fn ($record) => $record->products()->count() > 0)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('name', 'asc');
    }
}
