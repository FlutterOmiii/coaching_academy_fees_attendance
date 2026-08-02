<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('organizer')->nullable();
            $table->string('venue')->nullable();
            $table->enum('format', ['t10', 't20', 'odi', 'multi_day', 'custom'])->default('t20');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('entry_fee', 10, 2)->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            // Where the academy finished, e.g. "Winner", "Semi-finalist".
            $table->string('final_position', 50)->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date']);
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->enum('age_group', ['under_10', 'under_12', 'under_14', 'under_16', 'under_19', 'senior', 'open'])
                ->default('open');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['status', 'age_group']);
        });

        Schema::create('team_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('jersey_number')->nullable();
            $table->boolean('is_captain')->default(false);
            $table->boolean('is_vice_captain')->default(false);
            $table->enum('role', ['batsman', 'bowler', 'all_rounder', 'wicket_keeper'])->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'student_id']);
            $table->unique(['team_id', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_student');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('tournaments');
    }
};
