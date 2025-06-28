<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('abbreviation')->nullable();
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->decimal('sell_price', 10, 2)->default(0);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
}; 