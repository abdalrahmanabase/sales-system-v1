@php
use App\Helpers\FormatHelper;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
@endpush
<div class="stock-movements-container">
    <div class="stock-movements-header">
        <h4 class="stock-movements-title">Stock Movements</h4>
        <p class="stock-movements-subtitle">All stock movements for this entry</p>
    </div>

    @if($movements->count() > 0)
        <div class="stock-movements-list">
            @foreach($movements as $movement)
            <div class="stock-movement-item">
                <div class="stock-movement-header">
                    <div class="stock-movement-type {{ $movement->movement_type }}">
                        {{ ucfirst($movement->movement_type) }}
                    </div>
                    <div class="stock-movement-time">
                        {{ FormatHelper::formatDateTimeWithSeconds($movement->created_at) }}
                    </div>
                </div>
                
                <div class="stock-movement-details">
                    <div class="stock-movement-quantity {{ $movement->quantity > 0 ? 'positive' : 'negative' }}">
                        {{ $movement->quantity > 0 ? '+' : '' }}{{ FormatHelper::formatQuantity($movement->quantity) }}
                    </div>
                    
                    @if($movement->quantity_before !== null && $movement->quantity_after !== null)
                    <div class="stock-movement-before-after">
                        <span class="stock-movement-before">
                            Before: {{ FormatHelper::formatQuantity($movement->quantity_before) }}
                        </span>
                        <span class="stock-movement-arrow">→</span>
                        <span class="stock-movement-after">
                            After: {{ FormatHelper::formatQuantity($movement->quantity_after) }}
                        </span>
                    </div>
                    @endif
                    
                    @if($movement->source_type || $movement->source_reference)
                    <div class="stock-movement-source">
                        @if($movement->source_type)
                        <span class="stock-movement-source-type">
                            {{ ucwords(str_replace('_', ' ', $movement->source_type)) }}
                        </span>
                        @endif
                        @if($movement->source_reference)
                        <span class="stock-movement-reference">
                            {{ $movement->source_reference }}
                        </span>
                        @endif
                    </div>
                    @endif
                    
                    @if($movement->notes)
                    <div class="stock-movement-notes">
                        {{ $movement->notes }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Summary -->
        <div class="stock-movements-summary">
            <h5 class="stock-movements-summary-title">Movement Summary</h5>
            <div class="stock-movements-summary-grid">
                <div class="stock-movements-summary-item">
                    <span class="stock-movements-summary-label">Total Incoming:</span>
                    <span class="stock-movements-summary-value positive">
                        {{ FormatHelper::formatQuantity($movements->where('movement_type', 'in')->sum('quantity')) }}
                    </span>
                </div>
                <div class="stock-movements-summary-item">
                    <span class="stock-movements-summary-label">Total Outgoing:</span>
                    <span class="stock-movements-summary-value negative">
                        {{ FormatHelper::formatQuantity($movements->where('movement_type', 'out')->sum('quantity')) }}
                    </span>
                </div>
                <div class="stock-movements-summary-item">
                    <span class="stock-movements-summary-label">Total Adjustments:</span>
                    <span class="stock-movements-summary-value">
                        {{ FormatHelper::formatQuantity($movements->where('movement_type', 'adjustment')->sum('quantity')) }}
                    </span>
                </div>
                <div class="stock-movements-summary-item">
                    <span class="stock-movements-summary-label">Total Movements:</span>
                    <span class="stock-movements-summary-value">
                        {{ $movements->count() }}
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="stock-movements-empty">
            <div class="stock-movements-empty-icon">📊</div>
            <h5 class="stock-movements-empty-title">No Movements Found</h5>
            <p class="stock-movements-empty-subtitle">This stock entry has no recorded movements yet.</p>
        </div>
    @endif
</div> 