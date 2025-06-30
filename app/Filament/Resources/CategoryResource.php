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
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('Category Name')
                            ->placeholder('e.g., Electronics, Clothing, Food')
                            ->helperText('Enter a descriptive name for the category'),
                        Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Parent Category')
                            ->placeholder('Select parent category (optional)')
                            ->helperText('Leave empty to create a top-level category')
                            ->options(function () {
                                return Category::whereNull('parent_id')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),
                    ])->columns(2),
                Forms\Components\Section::make('Category Statistics')
                    ->schema([
                        Forms\Components\Placeholder::make('products_count')
                            ->label('Total Products')
                            ->content(fn ($record) => $record ? $record->products()->count() : 0),
                        Forms\Components\Placeholder::make('children_count')
                            ->label('Subcategories')
                            ->content(fn ($record) => $record ? $record->children()->count() : 0),
                        Forms\Components\Placeholder::make('full_path')
                            ->label('Category Path')
                            ->content(fn ($record) => $record ? $record->full_path : ''),
                    ])->columns(3)
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Category Name'),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Top Level'),
                Tables\Columns\TextColumn::make('full_path')
                    ->label('Full Path')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Subcategories')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state)),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatNumber($state)),
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
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Category')
                        ->modalDescription('Category details and information'),
                    Tables\Actions\EditAction::make()
                        ->modalHeading('Edit Category')
                        ->modalDescription('Update category information'),
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
            RelationManagers\ChildrenRelationManager::class,
            RelationManagers\ProductsRelationManager::class,
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
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

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }
}
