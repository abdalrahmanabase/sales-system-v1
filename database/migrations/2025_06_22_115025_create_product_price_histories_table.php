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
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->onDelete('set null');
            $table->decimal('old_purchase_price', 10, 2)->default(0);
            $table->decimal('new_purchase_price', 10, 2)->default(0);
            $table->decimal('old_sell_price', 10, 2)->default(0);
            $table->decimal('new_sell_price', 10, 2)->default(0);
            $table->timestamp('changed_at');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('change_reason')->default('manual_update'); // manual_update, invoice_update, system_update
            $table->string('source_type')->nullable(); // provider, sale, return, transfer, adjustment
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['product_id', 'unit_id']);
            $table->index(['changed_at']);
            $table->index(['change_reason']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
