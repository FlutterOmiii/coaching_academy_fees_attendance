<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('training_session_id')->nullable()->constrained('training_sessions')->nullOnDelete();

            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->time('check_in')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // One record per student per batch per day.
            $table->unique(['student_id', 'batch_id', 'attendance_date'], 'student_attendance_unique');
            $table->index(['attendance_date', 'status']);
            $table->index(['batch_id', 'attendance_date']);
        });

        Schema::create('coach_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'half_day', 'leave'])->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['coach_id', 'attendance_date'], 'coach_attendance_unique');
            $table->index(['attendance_date', 'status']);
        });

        // Leave requests for students and coaches (polymorphic).
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('leavable'); // leavable_type + leavable_id
            $table->date('from_date');
            $table->date('to_date');
            $table->enum('type', ['sick', 'personal', 'travel', 'exam', 'other'])->default('other');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'from_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('coach_attendances');
        Schema::dropIfExists('student_attendances');
    }
};
