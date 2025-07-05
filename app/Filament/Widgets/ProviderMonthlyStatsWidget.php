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
            // Only show stats for THIS MONTH
            $now = now();
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();

            $totalProviders = Provider::count();
            $totalPayments = ProviderPayment::whereBetween('payment_date', [$start, $end])->sum('amount') ?? 0;
            $totalPurchases = PurchaseInvoice::whereBetween('invoice_date', [$start, $end])->sum('total_amount') ?? 0;
            $totalDebt = $totalPurchases - $totalPayments;

            return [
                Stat::make('Total Providers', $totalProviders)
                    ->icon('heroicon-o-users')
                    ->description('Providers this month')
                    ->color('info'),
                Stat::make('Total Purchases', '$' . number_format($totalPurchases, 2))
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Purchases this month')
                    ->color('warning'),
                Stat::make('Total Debt', '$' . number_format($totalDebt, 2))
                    ->icon('heroicon-o-currency-dollar')
                    ->description('Debt this month')
                    ->color($totalDebt > 0 ? 'danger' : 'success'),
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