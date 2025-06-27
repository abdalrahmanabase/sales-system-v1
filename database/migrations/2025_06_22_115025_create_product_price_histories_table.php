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
            $table->decimal('old_purchase_price', 10, 2)->nullable();
            $table->decimal('new_purchase_price', 10, 2)->nullable();
            $table->decimal('old_sell_price', 10, 2)->nullable();
            $table->decimal('new_sell_price', 10, 2)->nullable();
            $table->timestamp('changed_at');
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->string('change_reason')->nullable(); // e.g., 'invoice_update', 'manual_update'
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'changed_at']);
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
