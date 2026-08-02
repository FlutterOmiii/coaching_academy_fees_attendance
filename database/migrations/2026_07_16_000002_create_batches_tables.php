<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();

            // Head coach. Assistant coaches live in batch_coach.
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();

            $table->enum('age_group', ['under_10', 'under_12', 'under_14', 'under_16', 'under_19', 'senior', 'open'])
                ->default('open');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'professional'])
                ->default('beginner');

            $table->unsignedSmallInteger('capacity')->default(20);
            $table->time('start_time');
            $table->time('end_time');
            // Training days as day-of-week integers, e.g. [1,3,5]
            $table->json('training_days');
            $table->string('ground')->nullable();

            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'age_group']);
        });

        // Additional coaches assigned to a batch.
        Schema::create('batch_coach', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->enum('role', ['head', 'assistant', 'support'])->default('assistant');
            $table->date('assigned_on')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'coach_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_coach');
        Schema::dropIfExists('batches');
    }
};
