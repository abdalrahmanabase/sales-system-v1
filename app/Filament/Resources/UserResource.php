<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;
use App\Helpers\PermissionHelper;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create'),
                    Forms\Components\Select::make('branch_id')
                        ->label('Branch')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get) => !in_array('super-admin', $get('roles') ?? []) && !in_array('admin', $get('roles') ?? [])),
                ])->columns(2),

            Forms\Components\Section::make('Roles & Permissions')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->multiple()
                        ->relationship('roles', 'name')
                        ->preload()
                        ->searchable()
                        ->options(function () {
                            $roles = \Spatie\Permission\Models\Role::query();
                            if (!auth()->user()->hasRole('super-admin')) {
                                $roles->whereNotIn('name', ['super-admin', 'admin']);
                            }
                            return $roles->pluck('name', 'id');
                        }),
                    // Forms\Components\Select::make('permissions')
                    //     ->multiple()
                    //     ->relationship('permissions', 'name')
                    //     ->preload()
                    //     ->searchable()
                    //     ->options(function () {
                    //         if (auth()->user()->hasRole('super-admin')) {
                    //             return \Spatie\Permission\Models\Permission::pluck('name', 'id');
                    //         }
                    //         $permissions = \Spatie\Permission\Models\Permission::query();
                    //         $permissions->whereNotIn('name', \App\Helpers\PermissionHelper::getSuperAdminOnlyPermissions());
                    //         return $permissions->pluck('name', 'id');
                    //     })
                    //     ->getSearchResultsUsing(function (string $search) {
                    //         $query = \Spatie\Permission\Models\Permission::query();
                    //         if (!auth()->user()->hasRole('super-admin')) {
                    //             $query->whereNotIn('name', \App\Helpers\PermissionHelper::getSuperAdminOnlyPermissions());
                    //         }
                    //         return $query->where('name', 'like', "%{$search}%")->pluck('name', 'id');
                    //     })
                    //     ->getOptionLabelUsing(fn ($value): ?string => \Spatie\Permission\Models\Permission::find($value)?->name),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->preload(),
            ])
            ->actions([
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('impersonate')
                        ->label('Impersonate')
                        ->icon('heroicon-o-arrow-right-on-rectangle')
                        ->action(function (User $record) {
                            // Add impersonation logic here
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->hasRole('super-admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                if (!auth()->user()->hasRole('super-admin')) {
                    $query->whereDoesntHave('roles', function ($q) {
                        $q->whereIn('name', ['super-admin', 'admin']);
                    });
                    $query->where('id', '!=', auth()->id()); // Admin cannot see/edit their own user record
                }
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'permissions']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }

    public static function canEdit(Model $record): bool
    {
        // Super-admin can edit all users including admin
        if (auth()->user()->hasRole('super-admin')) {
            return true;
        }
        
        // Admin can only edit non-admin and non-super-admin users
        if (auth()->user()->hasRole('admin')) {
            return !$record->hasRole('super-admin') && !$record->hasRole('admin');
        }
        
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        // Super-admin can delete all users except themselves
        if (auth()->user()->hasRole('super-admin')) {
            return $record->id !== auth()->id();
        }
        
        // Admin can only delete non-admin and non-super-admin users
        if (auth()->user()->hasRole('admin')) {
            return !$record->hasRole('super-admin') && !$record->hasRole('admin');
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']) || auth()->user()->id === $record->id;
    }
} 