@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @endpush
@endonce

<style>
    .barcode-scanner-container {
        position: relative;
    }
    .barcode-scanner-input {
        display: block;
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        background: #fff;
        color: #111;
        font-size: 0.875rem;
        transition: all 0.15s ease-in-out;
    }
    .barcode-scanner-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .barcode-scanner-input:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    .barcode-scanner-input-error {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }
    .barcode-scanner-indicator {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        padding-right: 0.75rem;
    }
    .barcode-scanner-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .barcode-scanner-dot {
        width: 0.5rem;
        height: 0.5rem;
        background: #22c55e;
        border-radius: 50%;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    .barcode-scanner-icon {
        width: 1.25rem;
        height: 1.25rem;
        color: #9ca3af;
    }
    .barcode-scanner-helper {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }
    .barcode-scanner-status-text {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        color: #9ca3af;
    }
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .barcode-scanner-input {
            border-color: #4b5563;
            background: #374151;
            color: #fff;
        }
        .barcode-scanner-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .barcode-scanner-input-error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }
        .barcode-scanner-icon {
            color: #6b7280;
        }
        .barcode-scanner-helper {
            color: #9ca3af;
        }
        .barcode-scanner-status-text {
            color: #6b7280;
        }
    }

    /* Filament dark mode support */
    .dark .barcode-scanner-input {
        border-color: #4b5563;
        background: #374151;
        color: #fff;
    }
    .dark .barcode-scanner-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    .dark .barcode-scanner-input-error {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
    }
    .dark .barcode-scanner-icon {
        color: #6b7280;
    }
    .dark .barcode-scanner-helper {
        color: #9ca3af;
    }
    .dark .barcode-scanner-status-text {
        color: #6b7280;
    }
</style>

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        barcode: @entangle($getStatePath()),
        barcodeBuffer: '',
        lastKeyTime: 0,
        
        init() {
            // Global barcode scanner listener
            document.addEventListener('keydown', (e) => {
                const currentTime = new Date().getTime();
                
                // Reset buffer if too much time has passed between keystrokes
                if (currentTime - this.lastKeyTime > 100) {
                    this.barcodeBuffer = '';
                }
                
                this.lastKeyTime = currentTime;
                
                // Add character to buffer
                if (e.key.length === 1) {
                    this.barcodeBuffer += e.key;
                }
                
                // Check for Enter key (barcode scanner typically sends Enter at the end)
                if (e.key === 'Enter' && this.barcodeBuffer.length > 0) {
                    e.preventDefault();
                    
                    // Set the barcode value
                    this.barcode = this.barcodeBuffer;
                    
                    // Clear buffer
                    this.barcodeBuffer = '';
                    
                    // Trigger change event
                    this.$dispatch('input', this.barcode);
                    
                    // Focus on the input field
                    this.$refs.barcodeInput.focus();
                }
            });
        }
    }">
        <div class="barcode-scanner-container">
            <input
                x-ref="barcodeInput"
                type="text"
                x-model="barcode"
                {!! $isAutofocused() ? 'autofocus' : null !!}
                {!! $isDisabled() ? 'disabled' : null !!}
                id="{{ $getId() }}"
                {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}"
                class="barcode-scanner-input @error($getStatePath()) barcode-scanner-input-error @enderror"
                placeholder="{{ $getPlaceholder() ?? 'Scan barcode or type manually' }}"
                {!! $isRequired() ? 'required' : null !!}
            />
            
            <!-- Barcode scanner indicator -->
            <div class="barcode-scanner-indicator">
                <div class="barcode-scanner-status">
                    <div class="barcode-scanner-dot" 
                         x-show="barcodeBuffer.length > 0"
                         x-transition></div>
                    <svg class="barcode-scanner-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zm12 0h2a1 1 0 001-1V6a1 1 0 00-1-1h-2a1 1 0 00-1 1v1a1 1 0 001 1zM5 20h2a1 1 0 001-1v-1a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Helper text -->
        @if ($getHelperText())
            <p class="barcode-scanner-helper">
                {{ $getHelperText() }}
            </p>
        @endif
        
        <!-- Barcode scanner status -->
        <div class="barcode-scanner-status-text" x-show="barcodeBuffer.length > 0" x-transition>
            <span x-text="`Listening for barcode... (${barcodeBuffer.length} chars)`"></span>
        </div>
    </div>
</x-dynamic-component> 