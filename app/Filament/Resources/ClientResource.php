<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\FormatHelper;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Client Name'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->label('Notes'),
                    ])->columns(2),

                Forms\Components\Section::make('Phone Numbers')
                    ->schema([
                        Forms\Components\Repeater::make('phones')
                            ->relationship('phones')
                            ->schema([
                                Forms\Components\TextInput::make('phone_number')
                                    ->required()
                                    ->tel()
                                    ->maxLength(20)
                                    ->label('Phone Number'),
                                Forms\Components\Select::make('label')
                                    ->options([
                                        'main' => 'Main',
                                        'mobile' => 'Mobile',
                                        'whatsapp' => 'WhatsApp',
                                        'telegram' => 'Telegram',
                                        'work' => 'Work',
                                        'home' => 'Home',
                                        'backup' => 'Backup',
                                        'other' => 'Other',
                                    ])
                                    ->default('main')
                                    ->searchable()
                                    ->label('Label'),
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Primary Phone')
                                    ->default(false),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['phone_number'] ?? null),
                    ])->collapsible(),

                Forms\Components\Section::make('Addresses')
                    ->schema([
                        Forms\Components\Repeater::make('addresses')
                            ->relationship('addresses')
                            ->schema([
                                Forms\Components\Select::make('label')
                                    ->options([
                                        'home' => 'Home',
                                        'work' => 'Work',
                                        'office' => 'Office',
                                        'warehouse' => 'Warehouse',
                                        'branch' => 'Branch',
                                        'billing' => 'Billing Address',
                                        'shipping' => 'Shipping Address',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->default('home')
                                    ->searchable()
                                    ->label('Address Label'),
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(100)
                                    ->label('City'),
                                Forms\Components\TextInput::make('area')
                                    ->maxLength(100)
                                    ->label('Area'),
                                Forms\Components\TextInput::make('street')
                                    ->maxLength(255)
                                    ->label('Street'),
                                Forms\Components\TextInput::make('building')
                                    ->maxLength(100)
                                    ->label('Building'),
                                Forms\Components\TextInput::make('floor')
                                    ->maxLength(20)
                                    ->label('Floor'),
                                Forms\Components\TextInput::make('apartment')
                                    ->maxLength(20)
                                    ->label('Apartment'),
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Address')
                                    ->default(false),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                    ])->collapsible(),
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
                Tables\Columns\TextColumn::make('phones.phone_number')
                    ->label('Primary Phone')
                    ->getStateUsing(function (Client $record) {
                        $primaryPhone = $record->phones()->where('is_primary', true)->first();
                        return $primaryPhone ? $primaryPhone->phone_number : $record->phones()->first()?->phone_number;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('sales_count')
                    ->counts('sales')
                    ->label('Total Sales')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->getStateUsing(function (Client $record) {
                        return $record->sales()->sum('final_total') ?: $record->sales()->sum('total_amount');
                    })
                    ->formatStateUsing(fn ($state) => FormatHelper::formatCurrency($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_sale_date')
                    ->label('Last Purchase')
                    ->getStateUsing(function (Client $record) {
                        return $record->sales()->latest()->first()?->sale_date;
                    })
                    ->formatStateUsing(fn ($state) => $state ? FormatHelper::formatDate($state) : 'No purchases')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_sales')
                    ->label('Has Sales History')
                    ->query(fn (Builder $query): Builder => $query->has('sales')),
                Tables\Filters\Filter::make('no_sales')
                    ->label('No Sales History')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('sales')),
                Tables\Filters\Filter::make('recent_sales')
                    ->label('Recent Sales (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('sales', function ($q) {
                        $q->where('sale_date', '>=', now()->subDays(30));
                    })),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_sales')
                        ->label('View Sales History')
                        ->icon('heroicon-o-shopping-cart')
                        ->url(fn (Client $record): string => route('filament.admin.resources.clients.view', ['record' => $record])),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['phones', 'addresses', 'sales']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create clients');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit clients');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete clients') && !$record->sales()->exists();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view clients');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view clients');
    }
}
