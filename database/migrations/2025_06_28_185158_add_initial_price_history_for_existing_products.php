<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add initial price history records for existing products that don't have any
        $products = DB::table('products')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_price_histories')
                    ->whereRaw('product_price_histories.product_id = products.id');
            })
            ->get();
        
        foreach ($products as $product) {
            // Get the base unit for this product
            $baseUnit = DB::table('product_units')
                ->where('product_id', $product->id)
                ->where('is_base_unit', true)
                ->first();
            
            // Create initial price history record
            DB::table('product_price_histories')->insert([
                'product_id' => $product->id,
                'unit_id' => $baseUnit ? $baseUnit->id : null,
                'old_purchase_price' => 0,
                'new_purchase_price' => $product->purchase_price_per_unit ?? 0,
                'old_sell_price' => 0,
                'new_sell_price' => $product->sell_price_per_unit ?? 0,
                'changed_at' => $product->created_at ?? now(),
                'changed_by' => null, // System created
                'change_reason' => 'product_creation',
                'source_type' => 'creation',
                'source_id' => null,
                'source_reference' => 'Product Creation',
                'notes' => 'Product created with initial prices',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove initial price history records that were created by this migration
        DB::table('product_price_histories')
            ->where('change_reason', 'product_creation')
            ->where('source_type', 'creation')
            ->delete();
    }
};
