<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('request_reference')->unique();
            $table->enum('leave_type', ['annual', 'sick', 'maternity', 'paternity', 'emergency', 'unpaid', 'study', 'compassionate'])->default('annual');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('manager_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('attachment')->nullable(); // Medical certificate, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_requests');
    }
};