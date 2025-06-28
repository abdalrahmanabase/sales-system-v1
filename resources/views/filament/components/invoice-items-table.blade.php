@php
use App\Helpers\FormatHelper;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
@endpush
<div class="invoice-container">
    <div class="invoice-header">
        <h4 class="invoice-title">Purchase Invoice Details</h4>
    </div>

    <div class="invoice-details">
        <div class="invoice-details-item">
            <span class="invoice-details-label">Invoice Number:</span>
            <span class="invoice-details-value">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="invoice-details-item">
            <span class="invoice-details-label">Date:</span>
            <span class="invoice-details-value">{{ FormatHelper::formatDate($invoice->invoice_date) }}</span>
        </div>
        <div class="invoice-details-item">
            <span class="invoice-details-label">Total Amount:</span>
            <span class="invoice-details-value">{{ FormatHelper::formatCurrency($invoice->total_amount) }}</span>
        </div>
    </div>

    @if($items->count() > 0)
        <div class="invoice-table-container">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Purchase Price</th>
                        <th>Total</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <div class="invoice-product-info">
                                <div class="invoice-product-name">{{ $item->product->name }}</div>
                                <div class="invoice-product-barcode">{{ $item->product->barcode ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td>{{ $item->unit?->name ?? 'Base Unit' }}</td>
                        <td>{{ FormatHelper::formatNumber($item->quantity, 0) }}</td>
                        <td>{{ FormatHelper::formatCurrency($item->purchase_price) }}</td>
                        <td>{{ FormatHelper::formatCurrency($item->quantity * $item->purchase_price) }}</td>
                        <td>
                            <span class="invoice-item-type {{ $item->is_bonus ? 'bonus' : 'regular' }}">
                                {{ $item->is_bonus ? 'Bonus' : 'Regular' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="invoice-total-label">Regular Items Total:</td>
                        <td class="invoice-total-value">
                            {{ FormatHelper::formatCurrency($items->where('is_bonus', false)->sum(function($item) { return $item->quantity * $item->purchase_price; })) }}
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="invoice-total-label">Bonus Items Total:</td>
                        <td class="invoice-total-value">
                            {{ FormatHelper::formatCurrency($items->where('is_bonus', true)->sum(function($item) { return $item->quantity * $item->purchase_price; })) }}
                        </td>
                        <td></td>
                    </tr>
                    <tr class="invoice-grand-total">
                        <td colspan="4" class="invoice-total-label">Grand Total:</td>
                        <td class="invoice-total-value">
                            {{ FormatHelper::formatCurrency($invoice->total_amount) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="invoice-no-items">
            <p>No items found for this invoice.</p>
        </div>
    @endif
</div> 