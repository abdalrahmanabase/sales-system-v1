<?php

namespace App\Filament\Components;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

class GlobalBarcodeScanner extends TextInput
{
    protected string $view = 'filament.components.global-barcode-scanner';

    public static function make(string $name): static
    {
        return parent::make($name)
            ->label('Barcode Scanner')
            ->placeholder('Scan barcode or type manually')
                ->autofocus()
            ->live(onBlur: true);
    }
} 