<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Permission;
use App\Helpers\PermissionHelper;
use App\Helpers\FormatHelper;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('guard_name')
                            ->default('web')
                            ->disabled()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\Select::make('permissions')
                            ->multiple()
                            ->relationship('permissions', 'name')
                            ->preload()
                            ->searchable()
                            ->options(function () {
                                if (auth()->user()->hasRole('super-admin')) {
                                    return \Spatie\Permission\Models\Permission::pluck('name', 'id');
                                }
                                $permissions = \Spatie\Permission\Models\Permission::query();
                                $permissions->whereNotIn('name', \App\Helpers\PermissionHelper::getSuperAdminOnlyPermissions());
                                return $permissions->pluck('name', 'id');
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $query = \Spatie\Permission\Models\Permission::query();
                                if (!auth()->user()->hasRole('super-admin')) {
                                    $query->whereNotIn('name', \App\Helpers\PermissionHelper::getSuperAdminOnlyPermissions());
                                }
                                return $query->where('name', 'like', "%{$search}%")->pluck('name', 'id');
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \Spatie\Permission\Models\Permission::find($value)?->name),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users'),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => FormatHelper::formatDateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
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
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                if (!auth()->user()->hasRole('super-admin')) {
                    $query->where('name', '!=', 'super-admin');
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }

    public static function canEdit(Model $record): bool
    {
        // Super-admin can edit all roles
        if (auth()->user()->hasRole('super-admin')) {
            return true;
        }
        
        // Admin can only edit non-super-admin and non-admin roles
        if (auth()->user()->hasRole('admin')) {
            return !in_array($record->name, ['super-admin', 'admin']);
        }
        
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        // Super-admin can delete all roles except super-admin
        if (auth()->user()->hasRole('super-admin')) {
            return $record->name !== 'super-admin';
        }
        
        // Admin can only delete non-super-admin and non-admin roles
        if (auth()->user()->hasRole('admin')) {
            return !in_array($record->name, ['super-admin', 'admin']);
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }
}
