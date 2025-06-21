<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->nullable();
            $table->string('from_location_type')->nullable(); // polymorphic
            $table->unsignedBigInteger('from_location_id')->nullable(); // polymorphic
            $table->string('to_location_type')->nullable(); // polymorphic
            $table->unsignedBigInteger('to_location_id')->nullable(); // polymorphic
            $table->date('transfer_date')->nullable();
            $table->enum('status', ['pending', 'in_transit', 'completed'])->default('completed');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
}; 