<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Provider;
use App\Models\ProviderPayment;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Carbon;

class ProviderMonthlyStatsWidget extends BaseWidget
{
    public ?string $period = 'this_month';
    
    protected static bool $isLazy = false;
    
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        try {
            // Simple stats for testing
            $totalProviders = Provider::count();
            $totalPayments = ProviderPayment::sum('amount') ?? 0;
            $totalPurchases = PurchaseInvoice::sum('total_amount') ?? 0;

            return [
                Stat::make('Total Providers', $totalProviders)
                    ->description('Number of providers')
                    ->color('info'),
                Stat::make('Total Payments', '$' . number_format($totalPayments, 2))
                    ->description('All time payments')
                    ->color('success'),
                Stat::make('Total Purchases', '$' . number_format($totalPurchases, 2))
                    ->description('All time purchases')
                    ->color('warning'),
            ];
        } catch (\Exception $e) {
            // Return error stats if there's an issue
            return [
                Stat::make('Error', 'Widget Error')
                    ->description('Check logs for details')
                    ->color('danger'),
            ];
        }
    }
} 