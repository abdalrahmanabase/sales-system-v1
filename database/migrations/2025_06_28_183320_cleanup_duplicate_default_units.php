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
        // Clean up duplicate default units
        // For each product, keep only one default unit and remove duplicates
        $products = DB::table('products')->get();
        
        foreach ($products as $product) {
            // Get all default units for this product
            $defaultUnits = DB::table('product_units')
                ->where('product_id', $product->id)
                ->where('name', 'Piece')
                ->where('abbreviation', 'pcs')
                ->where('conversion_factor', 1)
                ->where('is_base_unit', true)
                ->orderBy('id')
                ->get();
            
            if ($defaultUnits->count() > 1) {
                // Keep the first one, delete the rest
                $firstUnit = $defaultUnits->first();
                $duplicates = $defaultUnits->skip(1);
                
                foreach ($duplicates as $duplicate) {
                    DB::table('product_units')->where('id', $duplicate->id)->delete();
                }
                
                // Update product's base_unit_id to point to the first unit
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['base_unit_id' => $firstUnit->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is for cleanup, no rollback needed
    }
};
