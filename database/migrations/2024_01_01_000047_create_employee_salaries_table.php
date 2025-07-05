<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payroll_reference')->unique();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('payment_date');
            
            // Earnings
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('commissions', 10, 2)->default(0);
            $table->decimal('gross_salary', 15, 2);
            
            // Deductions
            $table->decimal('income_tax', 10, 2)->default(0);
            $table->decimal('social_security', 10, 2)->default(0);
            $table->decimal('health_insurance', 10, 2)->default(0);
            $table->decimal('pension_contribution', 10, 2)->default(0);
            $table->decimal('loan_deductions', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            
            // Net Pay
            $table->decimal('net_salary', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_remaining', 15, 2)->default(0);
            
            // Payment Information
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'mobile_money'])->default('bank_transfer');
            $table->enum('status', ['draft', 'pending', 'paid', 'partially_paid', 'cancelled'])->default('draft');
            $table->text('payment_notes')->nullable();
            $table->json('deduction_breakdown')->nullable(); // JSON breakdown of deductions
            $table->json('allowance_breakdown')->nullable(); // JSON breakdown of allowances
            
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};