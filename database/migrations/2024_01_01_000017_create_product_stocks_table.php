<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('unit_id')->nullable(); // Will be constrained later
            $table->decimal('quantity', 10, 4)->default(0);
            $table->string('source_type')->nullable(); // provider, sale, return, transfer, adjustment
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['product_id', 'warehouse_id', 'branch_id']);
            $table->index(['source_type', 'source_id']);
            $table->index('last_updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
}; 