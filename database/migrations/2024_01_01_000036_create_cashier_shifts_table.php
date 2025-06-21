<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->decimal('starting_cash_amount', 10, 2);
            $table->decimal('ending_cash_amount', 10, 2)->nullable();
            $table->decimal('start_sale_invoice_number', 10, 0)->nullable();
            $table->decimal('ending_sale_invoice_number', 10, 0)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
}; 