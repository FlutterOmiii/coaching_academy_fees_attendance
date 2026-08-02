<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named cricket_matches rather than matches: "match" is a reserved
        // keyword in PHP 8, so the model cannot be called Match.
        Schema::create('cricket_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->string('opponent_name');
            $table->date('match_date');
            $table->time('start_time')->nullable();
            $table->string('venue')->nullable();
            $table->enum('match_type', ['practice', 'friendly', 'tournament', 'league', 'knockout', 'final'])
                ->default('friendly');
            $table->unsignedSmallInteger('overs')->nullable();

            $table->enum('toss_won_by', ['academy', 'opponent'])->nullable();
            $table->enum('toss_decision', ['bat', 'bowl'])->nullable();

            $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled', 'abandoned'])
                ->default('scheduled');
            $table->enum('result', ['won', 'lost', 'tie', 'draw', 'no_result'])->nullable();
            $table->string('win_margin', 100)->nullable();

            // Academy innings
            $table->unsignedSmallInteger('academy_runs')->nullable();
            $table->unsignedTinyInteger('academy_wickets')->nullable();
            $table->decimal('academy_overs', 4, 1)->nullable();

            // Opponent innings
            $table->unsignedSmallInteger('opponent_runs')->nullable();
            $table->unsignedTinyInteger('opponent_wickets')->nullable();
            $table->decimal('opponent_overs', 4, 1)->nullable();

            $table->foreignId('man_of_match_id')->nullable()->constrained('students')->nullOnDelete();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['status', 'match_date']);
            $table->index('result');
        });

        Schema::create('match_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cricket_match_id')->constrained('cricket_matches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            // Batting
            $table->unsignedTinyInteger('batting_position')->nullable();
            $table->unsignedSmallInteger('runs_scored')->default(0);
            $table->unsignedSmallInteger('balls_faced')->default(0);
            $table->unsignedSmallInteger('fours')->default(0);
            $table->unsignedSmallInteger('sixes')->default(0);
            $table->boolean('is_out')->default(false);
            $table->enum('dismissal_type', [
                'bowled', 'caught', 'lbw', 'run_out', 'stumped', 'hit_wicket', 'retired', 'not_out',
            ])->nullable();

            // Bowling
            $table->decimal('overs_bowled', 4, 1)->default(0);
            $table->unsignedTinyInteger('maidens')->default(0);
            $table->unsignedSmallInteger('runs_conceded')->default(0);
            $table->unsignedTinyInteger('wickets')->default(0);
            $table->unsignedSmallInteger('wides')->default(0);
            $table->unsignedSmallInteger('no_balls')->default(0);

            // Fielding
            $table->unsignedTinyInteger('catches')->default(0);
            $table->unsignedTinyInteger('run_outs')->default(0);
            $table->unsignedTinyInteger('stumpings')->default(0);

            $table->decimal('rating', 3, 1)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['cricket_match_id', 'student_id'], 'match_performance_unique');
            $table->index('student_id');
        });

        // Periodic coach assessment of a student.
        Schema::create('student_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->date('assessment_date');

            $table->unsignedTinyInteger('batting_rating')->default(5);
            $table->unsignedTinyInteger('bowling_rating')->default(5);
            $table->unsignedTinyInteger('fielding_rating')->default(5);
            $table->unsignedTinyInteger('fitness_rating')->default(5);
            $table->unsignedTinyInteger('discipline_rating')->default(5);
            $table->decimal('overall_rating', 3, 1)->storedAs(
                '(batting_rating + bowling_rating + fielding_rating + fitness_rating + discipline_rating) / 5'
            );

            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assessments');
        Schema::dropIfExists('match_performances');
        Schema::dropIfExists('cricket_matches');
    }
};
