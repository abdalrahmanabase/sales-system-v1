<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('provider_sale_id')->nullable()->after('provider_id')->constrained('provider_sales')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['provider_sale_id']);
            $table->dropColumn('provider_sale_id');
        });
    }
}; 