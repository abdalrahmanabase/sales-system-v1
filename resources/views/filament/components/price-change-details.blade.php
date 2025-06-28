@php
use App\Helpers\FormatHelper;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
@endpush
<div class="price-details-container">
    <!-- Basic Information -->
    <div class="price-details-card">
        <h4 class="price-details-title">Price Change Details</h4>
        
        <div class="price-details-grid">
            <div>
                <div class="price-details-section-title">Product Information</div>
                <div class="price-details-list">
                    <div class="price-details-item">
                        <span class="price-details-label">Product:</span>
                        <span class="price-details-value">{{ $record->product->name }}</span>
                    </div>
                    <div class="price-details-item">
                        <span class="price-details-label">Barcode:</span>
                        <span class="price-details-value">{{ $record->product->barcode ?? 'N/A' }}</span>
                    </div>
                    <div class="price-details-item">
                        <span class="price-details-label">Unit:</span>
                        <span class="price-details-value">{{ $record->unit?->name ?? 'Base Unit' }}</span>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="price-details-section-title">Change Information</div>
                <div class="price-details-list">
                    <div class="price-details-item">
                        <span class="price-details-label">Changed At:</span>
                        <span class="price-details-value">{{ FormatHelper::formatDateTimeWithSeconds($record->changed_at) }}</span>
                    </div>
                    <div class="price-details-item">
                        <span class="price-details-label">Changed By:</span>
                        <span class="price-details-value">{{ $record->changedBy?->name ?? 'System' }}</span>
                    </div>
                    <div class="price-details-item">
                        <span class="price-details-label">Reason:</span>
                        <span class="price-details-value">{{ ucwords(str_replace('_', ' ', $record->change_reason)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Price Changes -->
    <div class="price-details-card">
        <h4 class="price-details-title">Purchase Price Changes</h4>
        
        <div class="price-change-grid">
            <div class="price-change-card">
                <div class="price-change-amount">
                    {{ FormatHelper::formatCurrency($record->old_purchase_price) }}
                </div>
                <div class="price-change-label">Old Price</div>
            </div>
            
            <div class="price-change-card">
                <div class="price-change-amount">
                    {{ FormatHelper::formatCurrency($record->new_purchase_price) }}
                </div>
                <div class="price-change-label">New Price</div>
            </div>
            
            <div class="price-change-card">
                <div class="price-change-amount {{ $record->purchase_price_change_percentage > 0 ? 'price-increase' : ($record->purchase_price_change_percentage < 0 ? 'price-decrease' : '') }}">
                    {{ $record->purchase_price_change_percentage > 0 ? '+' : '' }}{{ FormatHelper::formatPercentage($record->purchase_price_change_percentage) }}
                </div>
                <div class="price-change-label">Change</div>
            </div>
        </div>
    </div>

    <!-- Sell Price Changes -->
    <div class="price-details-card">
        <h4 class="price-details-title">Sell Price Changes</h4>
        
        <div class="price-change-grid">
            <div class="price-change-card">
                <div class="price-change-amount">
                    {{ FormatHelper::formatCurrency($record->old_sell_price) }}
                </div>
                <div class="price-change-label">Old Price</div>
            </div>
            
            <div class="price-change-card">
                <div class="price-change-amount">
                    {{ FormatHelper::formatCurrency($record->new_sell_price) }}
                </div>
                <div class="price-change-label">New Price</div>
            </div>
            
            <div class="price-change-card">
                <div class="price-change-amount {{ $record->sell_price_change_percentage > 0 ? 'price-increase' : ($record->sell_price_change_percentage < 0 ? 'price-decrease' : '') }}">
                    {{ $record->sell_price_change_percentage > 0 ? '+' : '' }}{{ FormatHelper::formatPercentage($record->sell_price_change_percentage) }}
                </div>
                <div class="price-change-label">Change</div>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    @if($record->source_type || $record->source_reference || $record->notes)
    <div class="price-details-card">
        <h4 class="price-details-title">Additional Information</h4>
        
        <div class="price-details-grid">
            @if($record->source_type)
            <div>
                <div class="price-details-section-title">Source Information</div>
                <div class="price-details-list">
                    <div class="price-details-item">
                        <span class="price-details-label">Source Type:</span>
                        <span class="price-details-value">{{ ucwords(str_replace('_', ' ', $record->source_type)) }}</span>
                    </div>
                    @if($record->source_reference)
                    <div class="price-details-item">
                        <span class="price-details-label">Reference:</span>
                        <span class="price-details-value">{{ $record->source_reference }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            
            @if($record->notes)
            <div>
                <div class="price-details-section-title">Notes</div>
                <div class="price-details-notes">
                    {{ $record->notes }}
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div> 