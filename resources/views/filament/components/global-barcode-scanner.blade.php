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
        <div class="relative">
            <input
                x-ref="barcodeInput"
                type="text"
                x-model="barcode"
                {!! $isAutofocused() ? 'autofocus' : null !!}
                {!! $isDisabled() ? 'disabled' : null !!}
                id="{{ $getId() }}"
                {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}"
                class="block w-full transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 border-gray-300 dark:border-gray-600 @error($getStatePath()) border-danger-600 ring-danger-600 dark:border-danger-400 dark:ring-danger-400 @enderror"
                placeholder="{{ $getPlaceholder() ?? 'Scan barcode or type manually' }}"
                {!! $isRequired() ? 'required' : null !!}
            />
            
            <!-- Barcode scanner indicator -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse" 
                         x-show="barcodeBuffer.length > 0"
                         x-transition></div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V6a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1zm12 0h2a1 1 0 001-1V6a1 1 0 00-1-1h-2a1 1 0 00-1 1v1a1 1 0 001 1zM5 20h2a1 1 0 001-1v-1a1 1 0 00-1-1H5a1 1 0 00-1 1v1a1 1 0 001 1z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Helper text -->
        @if ($getHelperText())
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $getHelperText() }}
            </p>
        @endif
        
        <!-- Barcode scanner status -->
        <div class="mt-1 text-xs text-gray-400" x-show="barcodeBuffer.length > 0" x-transition>
            <span x-text="`Listening for barcode... (${barcodeBuffer.length} chars)`"></span>
        </div>
    </div>
</x-dynamic-component> 