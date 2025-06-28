<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode')->unique();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('provider_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('purchase_price_per_unit', 10, 2)->default(0);
            $table->decimal('sell_price_per_unit', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['category_id']);
            $table->index(['provider_id']);
            $table->index(['is_active']);
            $table->index(['stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};