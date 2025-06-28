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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->onDelete('set null');
            $table->string('source_type')->nullable(); // provider, sale, return, transfer, adjustment
            $table->unsignedBigInteger('source_id')->nullable(); // ID of the source record
            $table->string('source_reference')->nullable(); // Reference like "Invoice #123"
            $table->foreignId('product_stock_id')->nullable()->constrained('product_stocks')->onDelete('cascade');
            $table->decimal('quantity_before', 10, 4)->nullable();
            $table->decimal('quantity_after', 10, 4)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['product_stock_id']);
            $table->dropForeign(['changed_by']);
            $table->dropColumn([
                'unit_id',
                'source_type',
                'source_id',
                'source_reference',
                'product_stock_id',
                'quantity_before',
                'quantity_after',
                'changed_by'
            ]);
        });
    }
};
