<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\HeaderActions;


class ProviderSalesRelationManager extends RelationManager
{
    protected static string $relationship = 'providerSales';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone2')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('phone2'),
                Tables\Columns\TextColumn::make('notes')->limit(30),
            ])
            ->filters([
                //
            ])
           
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Edit Provider Sale')
                    ->form([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone2')->maxLength(255),
                        Forms\Components\Textarea::make('notes')->maxLength(65535),
                    ])
                    ->mountUsing(fn ($form, $record) => $form->fill($record->only(['name', 'phone', 'phone2', 'notes'])))
                    ->action(function (array $data, $record) {
                        $record->update($data);
                    })
                    ->modalSubmitActionLabel('Save Changes'),
                
                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Provider Sale')
                    ->modalDescription('Are you sure you want to delete this provider sale? This action cannot be undone.')
                    ->action(fn ($record) => $record->delete())
                    ->modalSubmitActionLabel('Delete'),
            ])
            ->HeaderActions([
                Tables\Actions\Action::make('create')
                    ->label('Create')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Create Provider Sale')
                    ->form([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone2')->maxLength(255),
                        Forms\Components\Textarea::make('notes')->maxLength(65535),
                    ])
                    ->action(function (array $data, $livewire) {
                        $data['provider_id'] = $livewire->getOwnerRecord()->id;
                        \App\Models\ProviderSale::create($data);
                    })
                    ->modalSubmitActionLabel('Create'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Authorization
    // (Removed canCreate, canEdit, canDelete, canView, canViewAny to rely solely on policy)
} 