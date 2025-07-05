<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('loan_reference')->unique();
            $table->enum('loan_type', ['personal', 'advance', 'emergency', 'housing', 'education', 'medical'])->default('personal');
            $table->decimal('loan_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('monthly_payment', 10, 2);
            $table->integer('installments_count');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['pending', 'approved', 'active', 'completed', 'defaulted', 'cancelled'])->default('pending');
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2);
            $table->text('purpose')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->json('guarantors')->nullable(); // JSON array of guarantor information
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};