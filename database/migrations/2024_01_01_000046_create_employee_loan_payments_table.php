<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payment_reference')->unique();
            $table->decimal('payment_amount', 10, 2);
            $table->decimal('principal_amount', 10, 2);
            $table->decimal('interest_amount', 10, 2)->default(0);
            $table->date('payment_date');
            $table->date('due_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'payroll_deduction', 'check'])->default('payroll_deduction');
            $table->enum('status', ['pending', 'paid', 'overdue', 'partially_paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_payments');
    }
};