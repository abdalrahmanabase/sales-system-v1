<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;
use App\Models\PurchaseInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Provider Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Purchase Invoices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255)
                            ->label('Invoice Number'),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->required()
                            ->label('Invoice Date'),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('$')
                            ->label('Total Amount')
                            ->readOnly(),
                    ])->columns(3),
                Forms\Components\Section::make('Provider & Location')
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->relationship('provider', 'name')
                            ->searchable()
                            ->required()
                            ->label('Provider'),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->label('Branch'),
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->label('Warehouse'),
                    ])->columns(3),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->label('Invoice Image'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->label('Notes'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('provider.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable()
                    ->label('Total'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Filter by Provider')
                    ->options(fn () => \App\Models\Provider::pluck('name', 'id')->toArray())
                    ->placeholder('All Providers'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filter by Branch')
                    ->options(fn () => \App\Models\Branch::pluck('name', 'id')->toArray())
                    ->placeholder('All Branches'),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->modalHeading('Edit Purchase Invoice')
                        ->modalDescription('Update invoice information.')
                        ->modalSubmitActionLabel('Update Invoice'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                // Decrease stock for all items when invoice is deleted
                                foreach ($record->items as $item) {
                                    if (!$item->is_bonus) {
                                        $item->product->decrement('stock', $item->quantity);
                                    }
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'view' => Pages\ViewPurchaseInvoice::route('/{record}'),
            'edit' => Pages\EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['provider', 'branch', 'warehouse', 'items.product'])
            ->withCount(['items']);
    }

    // Authorization
    public static function canCreate(): bool
    {
        return auth()->user()->can('create purchase invoices');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit purchase invoices');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete purchase invoices');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view purchase invoices');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view purchase invoices');
    }
}
