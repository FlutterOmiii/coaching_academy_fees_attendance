<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 20)->unique();

            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->string('blood_group', 5)->nullable();
            $table->string('photo')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('school_name')->nullable();

            // Guardian / parent contact
            $table->string('guardian_name');
            $table->string('guardian_phone', 20);
            $table->string('guardian_email')->nullable();
            $table->string('guardian_relation', 50)->nullable();

            // Cricket profile
            $table->enum('playing_role', ['batsman', 'bowler', 'all_rounder', 'wicket_keeper'])->default('batsman');
            $table->enum('batting_style', ['right_hand', 'left_hand'])->nullable();
            $table->enum('bowling_style', [
                'right_arm_fast', 'right_arm_medium', 'right_arm_off_spin', 'right_arm_leg_spin',
                'left_arm_fast', 'left_arm_medium', 'left_arm_orthodox', 'left_arm_chinaman', 'none',
            ])->nullable();

            $table->date('admission_date');
            $table->enum('admission_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('medical_notes')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'admission_status']);
            $table->index('admission_date');
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('type', [
                'photo', 'birth_certificate', 'id_proof', 'address_proof',
                'medical_certificate', 'school_id', 'other',
            ])->default('other');
            $table->string('title');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'type']);
        });

        // Current + historical batch allocation.
        Schema::create('batch_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('joined_on');
            $table->date('left_on')->nullable();
            $table->enum('status', ['active', 'transferred', 'left'])->default('active');
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['batch_id', 'status']);
        });

        Schema::create('batch_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('to_batch_id')->constrained('batches')->cascadeOnDelete();
            $table->date('transferred_on');
            $table->string('reason')->nullable();
            $table->foreignId('transferred_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_transfers');
        Schema::dropIfExists('batch_student');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('students');
    }
};
