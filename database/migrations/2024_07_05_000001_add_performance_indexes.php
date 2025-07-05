<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            // Compound index for common filtering
            $table->index(['category_id', 'provider_id', 'is_active'], 'idx_products_category_provider_active');
            
            // Index for stock queries
            $table->index(['stock', 'low_stock_threshold'], 'idx_products_stock_threshold');
            
            // Index for barcode searches
            $table->index('barcode', 'idx_products_barcode');
            
            // Index for provider filtering
            $table->index('provider_id', 'idx_products_provider');
            
            // Index for category filtering
            $table->index(['category_id', 'subcategory_id'], 'idx_products_categories');
        });

        // Product stocks table indexes
        Schema::table('product_stocks', function (Blueprint $table) {
            // Compound index for location-based queries
            $table->index(['product_id', 'warehouse_id', 'branch_id'], 'idx_product_stocks_location');
            
            // Index for quantity queries
            $table->index(['quantity', 'product_id'], 'idx_product_stocks_quantity');
            
            // Index for warehouse filtering
            $table->index('warehouse_id', 'idx_product_stocks_warehouse');
            
            // Index for branch filtering
            $table->index('branch_id', 'idx_product_stocks_branch');
        });

        // Purchase invoices table indexes
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Compound index for provider and date filtering
            $table->index(['provider_id', 'invoice_date'], 'idx_purchase_invoices_provider_date');
            
            // Index for date range queries
            $table->index('invoice_date', 'idx_purchase_invoices_date');
            
            // Index for branch filtering
            $table->index('branch_id', 'idx_purchase_invoices_branch');
            
            // Index for warehouse filtering
            $table->index('warehouse_id', 'idx_purchase_invoices_warehouse');
        });

        // Stock movements table indexes
        Schema::table('stock_movements', function (Blueprint $table) {
            // Compound index for product and date queries
            $table->index(['product_id', 'created_at'], 'idx_stock_movements_product_date');
            
            // Index for movement type filtering
            $table->index('movement_type', 'idx_stock_movements_type');
            
            // Index for source tracking
            $table->index(['source_type', 'source_id'], 'idx_stock_movements_source');
            
            // Index for warehouse filtering
            $table->index('warehouse_id', 'idx_stock_movements_warehouse');
        });

        // Provider payments table indexes
        Schema::table('provider_payments', function (Blueprint $table) {
            // Compound index for provider and date
            $table->index(['provider_id', 'payment_date'], 'idx_provider_payments_provider_date');
            
            // Index for invoice linking
            $table->index('purchase_invoice_id', 'idx_provider_payments_invoice');
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            // Index for parent-child relationships
            $table->index('parent_id', 'idx_categories_parent');
            
            // Index for active categories
            $table->index('is_active', 'idx_categories_active');
        });

        // Product units table indexes
        Schema::table('product_units', function (Blueprint $table) {
            // Compound index for product and active units
            $table->index(['product_id', 'is_active'], 'idx_product_units_product_active');
            
            // Index for base unit queries
            $table->index('is_base_unit', 'idx_product_units_base');
        });

        // Sale items table indexes (if it exists)
        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                // Index for product sales analysis
                $table->index(['product_id', 'created_at'], 'idx_sale_items_product_date');
                
                // Index for sale tracking
                $table->index('sale_id', 'idx_sale_items_sale');
            });
        }

        // Purchase invoice items table indexes
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            // Index for product purchase analysis
            $table->index(['product_id', 'created_at'], 'idx_purchase_items_product_date');
            
            // Index for invoice items
            $table->index('purchase_invoice_id', 'idx_purchase_items_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        
        // Purchase invoice items
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_items_product_date');
            $table->dropIndex('idx_purchase_items_invoice');
        });

        // Sale items (if exists)
        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropIndex('idx_sale_items_product_date');
                $table->dropIndex('idx_sale_items_sale');
            });
        }

        // Product units
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropIndex('idx_product_units_product_active');
            $table->dropIndex('idx_product_units_base');
        });

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_parent');
            $table->dropIndex('idx_categories_active');
        });

        // Provider payments
        Schema::table('provider_payments', function (Blueprint $table) {
            $table->dropIndex('idx_provider_payments_provider_date');
            $table->dropIndex('idx_provider_payments_invoice');
        });

        // Stock movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_product_date');
            $table->dropIndex('idx_stock_movements_type');
            $table->dropIndex('idx_stock_movements_source');
            $table->dropIndex('idx_stock_movements_warehouse');
        });

        // Purchase invoices
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_invoices_provider_date');
            $table->dropIndex('idx_purchase_invoices_date');
            $table->dropIndex('idx_purchase_invoices_branch');
            $table->dropIndex('idx_purchase_invoices_warehouse');
        });

        // Product stocks
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropIndex('idx_product_stocks_location');
            $table->dropIndex('idx_product_stocks_quantity');
            $table->dropIndex('idx_product_stocks_warehouse');
            $table->dropIndex('idx_product_stocks_branch');
        });

        // Products
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_category_provider_active');
            $table->dropIndex('idx_products_stock_threshold');
            $table->dropIndex('idx_products_barcode');
            $table->dropIndex('idx_products_provider');
            $table->dropIndex('idx_products_categories');
        });
    }
};