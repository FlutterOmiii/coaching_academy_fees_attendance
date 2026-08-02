<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('coach_code', 20)->unique();

            // Optional login account for the coach.
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->string('photo')->nullable();

            $table->string('email')->nullable()->unique();
            $table->string('phone', 20);
            $table->string('alt_phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();

            $table->enum('specialization', [
                'batting', 'bowling', 'fielding', 'wicket_keeping', 'fitness', 'all_round',
            ])->default('all_round');
            $table->string('qualification')->nullable();
            $table->string('certification_level')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);

            $table->date('joining_date');
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->text('bio')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'specialization']);
        });

        Schema::create('coach_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            // 0 = Sunday .. 6 = Saturday
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['coach_id', 'day_of_week', 'start_time'], 'coach_avail_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_availabilities');
        Schema::dropIfExists('coaches');
    }
};
