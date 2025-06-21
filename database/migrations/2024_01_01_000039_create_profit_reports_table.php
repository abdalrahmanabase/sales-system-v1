<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_period'); // e.g. '2025-06'
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('total_sales', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->decimal('total_expenses', 10, 2);
            $table->decimal('net_profit', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_reports');
    }
}; 