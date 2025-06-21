<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->enum('reward_type', ['free_product', 'percentage_discount', 'fixed_discount', 'free_shipping', 'cashback']);
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('quantity')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable(); // % or fixed amount
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // Cap on discount
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_rewards');
    }
}; 