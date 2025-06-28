@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
@endpush

@php
use App\Helpers\FormatHelper;
@endphp

<div class="stock-details-container">
    <!-- Basic Information -->
    <div class="stock-details-card">
        <h4 class="stock-details-title">Stock Details</h4>
        
        <div class="stock-details-grid">
            <div>
                <div class="stock-details-section-title">Product Information</div>
                <div class="stock-details-list">
                    <div class="stock-details-item">
                        <span class="stock-details-label">Product:</span>
                        <span class="stock-details-value">{{ $record->product->name }}</span>
                    </div>
                    <div class="stock-details-item">
                        <span class="stock-details-label">Barcode:</span>
                        <span class="stock-details-value">{{ $record->product->barcode ?? 'N/A' }}</span>
                    </div>
                    <div class="stock-details-item">
                        <span class="stock-details-label">Unit:</span>
                        <span class="stock-details-value">{{ $record->unit?->name ?? 'Base Unit' }}</span>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="stock-details-section-title">Location Information</div>
                <div class="stock-details-list">
                    <div class="stock-details-item">
                        <span class="stock-details-label">Warehouse:</span>
                        <span class="stock-details-value">{{ $record->warehouse?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="stock-details-item">
                        <span class="stock-details-label">Branch:</span>
                        <span class="stock-details-value">{{ $record->branch?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="stock-details-item">
                        <span class="stock-details-label">Current Stock:</span>
                        <span class="stock-details-value">{{ FormatHelper::formatQuantity($record->quantity) }} {{ $record->unit?->abbreviation ?? 'units' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Summary -->
    <div class="stock-details-card">
        <h4 class="stock-details-title">Stock Summary</h4>
        
        <div class="stock-details-grid">
            <div>
                <div class="stock-details-section-title">Timeline</div>
                <div class="stock-details-list">
                    <div class="stock-details-item">
                        <span class="stock-details-label">Last Updated:</span>
                        <span class="stock-details-value">{{ $record->last_updated_at ? FormatHelper::formatDateTimeWithSeconds($record->last_updated_at) : 'Never' }}</span>
                    </div>
                    <div class="stock-details-item">
                        <span class="stock-details-label">Source Type:</span>
                        <span class="stock-details-value">{{ ucwords(str_replace('_', ' ', $record->source_type ?? 'Manual')) }}</span>
                    </div>
                    @if($record->source_reference)
                    <div class="stock-details-item">
                        <span class="stock-details-label">Reference:</span>
                        <span class="stock-details-value">{{ $record->source_reference }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div>
                <div class="stock-details-section-title">Movement Summary</div>
                <div class="stock-summary-list">
                    @php
                        $movements = $record->stockMovements()->orderBy('created_at', 'desc')->get();
                        $totalIncoming = $movements->where('movement_type', 'in')->sum('quantity');
                        $totalOutgoing = $movements->where('movement_type', 'out')->sum('quantity');
                        $totalAdjustments = $movements->where('movement_type', 'adjustment')->sum('quantity');
                    @endphp
                    
                    <div class="stock-summary-item">
                        <span class="stock-summary-label">Total Incoming:</span>
                        <span class="stock-summary-value stock-summary-positive">{{ FormatHelper::formatQuantity($totalIncoming) }}</span>
                    </div>
                    <div class="stock-summary-item">
                        <span class="stock-summary-label">Total Outgoing:</span>
                        <span class="stock-summary-value stock-summary-negative">{{ FormatHelper::formatQuantity($totalOutgoing) }}</span>
                    </div>
                    <div class="stock-summary-item">
                        <span class="stock-summary-label">Total Adjustments:</span>
                        <span class="stock-summary-value">{{ FormatHelper::formatQuantity($totalAdjustments) }}</span>
                    </div>
                    <div class="stock-summary-item">
                        <span class="stock-summary-label">Total Movements:</span>
                        <span class="stock-summary-value">{{ $movements->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    @if($movements->count() > 0)
    <div class="stock-details-card">
        <h4 class="stock-details-title">Recent Movements</h4>
        <div class="stock-movements-recent">
            <h5 class="stock-movements-subtitle">Recent Movements</h5>
            <div class="stock-movements-list">
                @foreach($movements->take(5) as $movement)
                <div class="stock-movement-item">
                    <div class="stock-movement-info">
                        <span class="stock-movement-type">
                            {{ $movement->movement_type }}
                        </span>
                        <span class="stock-movement-time">
                            {{ FormatHelper::formatDateTime($movement->created_at) }}
                        </span>
                    </div>
                    <div class="stock-movement-quantity {{ $movement->quantity > 0 ? 'stock-movement-positive' : 'stock-movement-negative' }}">
                        {{ $movement->quantity > 0 ? '+' : '' }}{{ FormatHelper::formatQuantity($movement->quantity) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div> 