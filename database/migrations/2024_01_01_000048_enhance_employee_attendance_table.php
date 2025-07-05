<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_attendance', function (Blueprint $table) {
            $table->date('attendance_date')->after('branch_id');
            $table->time('scheduled_in_time')->nullable()->after('attendance_date');
            $table->time('scheduled_out_time')->nullable()->after('scheduled_in_time');
            $table->decimal('regular_hours', 8, 2)->default(0)->after('status');
            $table->decimal('overtime_hours', 8, 2)->default(0)->after('regular_hours');
            $table->decimal('break_hours', 8, 2)->default(0)->after('overtime_hours');
            $table->decimal('total_hours', 8, 2)->default(0)->after('break_hours');
            $table->text('notes')->nullable()->after('total_hours');
            $table->enum('leave_type', ['annual', 'sick', 'maternity', 'paternity', 'emergency', 'unpaid'])->nullable()->after('notes');
            $table->boolean('is_holiday')->default(false)->after('leave_type');
            $table->string('location')->nullable()->after('is_holiday');
            $table->json('break_times')->nullable()->after('location'); // JSON array of break times
        });
    }

    public function down(): void
    {
        Schema::table('employee_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_date', 'scheduled_in_time', 'scheduled_out_time', 'regular_hours',
                'overtime_hours', 'break_hours', 'total_hours', 'notes', 'leave_type',
                'is_holiday', 'location', 'break_times'
            ]);
        });
    }
};