<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Helpers\FormatHelper;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Category Name')
                            ->placeholder('e.g., Electronics, Clothing, Food'),
                        Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Parent Category')
                            ->placeholder('Select parent category (optional)')
                            ->helperText('Leave empty to create a top-level category'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Top Level'),
                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Subcategories')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Filter by Parent')
                    ->options(function () {
                        return Category::whereNull('parent_id')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('All Categories'),
                Tables\Filters\Filter::make('top_level')
                    ->label('Top Level Categories')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),
                Tables\Filters\Filter::make('has_subcategories')
                    ->label('Has Subcategories')
                    ->query(fn (Builder $query): Builder => $query->whereHas('children')),
                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query): Builder => $query->whereHas('products')),
                Tables\Filters\Filter::make('no_products')
                    ->label('No Products')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('products')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_subcategories')
                        ->label('View Subcategories')
                        ->icon('heroicon-o-chevron-down')
                        ->url(fn (Category $record): string => route('filament.admin.resources.categories.index', ['tableFilters[parent_id][value]' => $record->id]))
                        ->visible(fn (Category $record): bool => $record->children()->count() > 0),
                    Tables\Actions\Action::make('view_products')
                        ->label('View Products')
                        ->icon('heroicon-o-cube')
                        ->url(fn (Category $record): string => route('filament.admin.resources.categories.view', ['record' => $record]))
                        ->visible(fn (Category $record): bool => $record->products()->count() > 0),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Check if any category has products
                            foreach ($records as $record) {
                                if ($record->products()->count() > 0) {
                                    throw new \Exception("Cannot delete category '{$record->name}' because it has products. Please remove or reassign the products first.");
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // We can add relation managers for products and subcategories
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent', 'children', 'products'])
            ->withCount(['children', 'products']);
    }

    // Authorization
    public static function canCreate(): bool
    {
        return auth()->user()->can('create categories');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit categories');
    }

    public static function canDelete(Model $record): bool
    {
        // Prevent deletion if category has products
        if ($record->products()->count() > 0) {
            return false;
        }
        return auth()->user()->can('delete categories');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view categories');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view categories');
    }
}
