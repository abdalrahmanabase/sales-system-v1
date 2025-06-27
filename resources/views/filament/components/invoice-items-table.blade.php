<div class="space-y-4">
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Invoice Details</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Invoice Number:</span>
                <span class="ml-2 text-gray-900">{{ $invoice->invoice_number ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Date:</span>
                <span class="ml-2 text-gray-900">{{ $invoice->invoice_date->format('M d, Y') }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Total Amount:</span>
                <span class="ml-2 text-gray-900 font-semibold">${{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Items Count:</span>
                <span class="ml-2 text-gray-900">{{ $items->count() }}</span>
            </div>
        </div>
    </div>

    @if($items->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Barcode
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Unit Price
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $item->product->name ?? 'Unknown Product' }}
                                </div>
                                @if($item->product)
                                    <div class="text-sm text-gray-500">
                                        ID: {{ $item->product->id }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item->product->barcode ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($item->quantity) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($item->purchase_price, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${{ number_format($item->quantity * $item->purchase_price, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item->is_bonus)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Bonus
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Regular
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-700">
                            Regular Items Total:
                        </td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-900">
                            ${{ number_format($items->where('is_bonus', false)->sum(function($item) { return $item->quantity * $item->purchase_price; }), 2) }}
                        </td>
                        <td></td>
                    </tr>
                    @if($items->where('is_bonus', true)->count() > 0)
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-700">
                                Bonus Items Total:
                            </td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                ${{ number_format($items->where('is_bonus', true)->sum(function($item) { return $item->quantity * $item->purchase_price; }), 2) }}
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    <tr class="border-t-2 border-gray-300">
                        <td colspan="4" class="px-6 py-3 text-right text-lg font-bold text-gray-900">
                            Invoice Total:
                        </td>
                        <td class="px-6 py-3 text-lg font-bold text-gray-900">
                            ${{ number_format($invoice->total_amount, 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center py-8">
            <div class="text-gray-500 text-lg">No items found for this invoice.</div>
        </div>
    @endif
</div> 