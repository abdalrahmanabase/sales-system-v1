<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;

class ProviderBalanceWidget extends BaseWidget
{
    public ?Model $record = null;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [
                Stat::make('Error', 'Provider not available')
                    ->description('Could not load provider details for the widget.')
                    ->color('danger'),
            ];
        }

        $provider = $this->record;
        $totalPurchases = $provider->total_purchases;
        $totalPayments = $provider->total_payments;
        $currentBalance = $provider->balance;
        $totalInvoices = $provider->purchaseInvoices()->count();

        return [
            Stat::make('Left Debt', '$' . number_format($currentBalance, 2))
                ->description($currentBalance > 0 ? 'Outstanding balance' : 'Fully paid')
                ->descriptionIcon($currentBalance > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($currentBalance > 0 ? 'danger' : 'success'),

            Stat::make('Total Paid', '$' . number_format($totalPayments, 2))
                ->description('All time payments made')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make('Total Invoices', $totalInvoices)
                ->description('Number of purchase invoices')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Purchases', '$' . number_format($totalPurchases, 2))
                ->description('All time purchase amount')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
        ];
    }
} 