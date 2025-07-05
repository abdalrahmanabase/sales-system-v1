<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Personal Information
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('employee_id')->unique()->nullable()->after('id');
            $table->string('national_id')->unique()->nullable()->after('employee_id');
            $table->string('social_security_number')->nullable()->after('national_id');
            $table->string('passport_number')->nullable()->after('social_security_number');
            $table->date('date_of_birth')->nullable()->after('passport_number');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->string('nationality')->nullable()->after('gender');
            $table->string('marital_status')->nullable()->after('nationality');
            
            // Contact Information
            $table->string('phone')->nullable()->after('marital_status');
            $table->string('email')->nullable()->after('phone');
            $table->string('emergency_contact_name')->nullable()->after('email');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            
            // Address Information
            $table->text('address')->nullable()->after('emergency_contact_phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            
            // Employment Details
            $table->string('employee_type')->default('full_time')->after('job_title'); // full_time, part_time, contract, intern
            $table->string('department')->nullable()->after('employee_type');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('salary');
            $table->integer('working_hours_per_week')->default(40)->after('hourly_rate');
            $table->date('probation_end_date')->nullable()->after('hire_date');
            $table->date('contract_end_date')->nullable()->after('probation_end_date');
            $table->enum('employment_status', ['active', 'inactive', 'terminated', 'resigned'])->default('active')->after('contract_end_date');
            $table->date('termination_date')->nullable()->after('employment_status');
            $table->text('termination_reason')->nullable()->after('termination_date');
            
            // Financial Information
            $table->string('bank_account_number')->nullable()->after('termination_reason');
            $table->string('bank_name')->nullable()->after('bank_account_number');
            $table->string('bank_routing_number')->nullable()->after('bank_name');
            $table->string('tax_id')->nullable()->after('bank_routing_number');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_id');
            
            // Benefits
            $table->text('benefits')->nullable()->after('tax_rate');
            
            // Additional Information
            $table->string('profile_picture')->nullable()->after('benefits');
            $table->text('notes')->nullable()->after('profile_picture');
            $table->boolean('is_active')->default(true)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name', 'employee_id', 'national_id', 'social_security_number', 'passport_number',
                'date_of_birth', 'gender', 'nationality', 'marital_status', 'phone', 'email',
                'emergency_contact_name', 'emergency_contact_phone', 'address', 'city', 'state',
                'postal_code', 'country', 'employee_type', 'department', 'hourly_rate',
                'working_hours_per_week', 'probation_end_date', 'contract_end_date', 'employment_status',
                'termination_date', 'termination_reason', 'bank_account_number', 'bank_name',
                'bank_routing_number', 'tax_id', 'tax_rate', 'benefits', 'profile_picture', 'notes', 'is_active'
            ]);
        });
    }
};