<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('order_status', ['new', 'confirmed', 'canceled', 'delivered'])->default('new');
            $table->enum('source', ['call', 'whatsapp', 'other'])->default('call');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_orders');
    }
}; 