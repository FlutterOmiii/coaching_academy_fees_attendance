<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Modern cricket terminology across the software:
 *   batsman      → batter
 *   all_rounder  → batting_allrounder / bowling_allrounder (two distinct roles)
 *
 * Existing all-rounders are mapped to batting_allrounder as the starting
 * point; individual students can be switched to bowling_allrounder on edit.
 */
return new class extends Migration
{
    private const OLD = ['batsman', 'bowler', 'all_rounder', 'wicket_keeper'];

    private const NEW = ['batter', 'bowler', 'batting_allrounder', 'bowling_allrounder', 'wicket_keeper'];

    public function up(): void
    {
        // Widen each enum to accept both vocabularies, move the data, then
        // narrow to the new vocabulary only.
        $all = "'".implode("','", array_unique([...self::OLD, ...self::NEW]))."'";
        $new = "'".implode("','", self::NEW)."'";

        DB::statement("ALTER TABLE students MODIFY playing_role ENUM({$all}) NOT NULL DEFAULT 'batsman'");
        DB::table('students')->where('playing_role', 'batsman')->update(['playing_role' => 'batter']);
        DB::table('students')->where('playing_role', 'all_rounder')->update(['playing_role' => 'batting_allrounder']);
        DB::statement("ALTER TABLE students MODIFY playing_role ENUM({$new}) NOT NULL DEFAULT 'batter'");

        DB::statement("ALTER TABLE team_student MODIFY role ENUM({$all}) NULL");
        DB::table('team_student')->where('role', 'batsman')->update(['role' => 'batter']);
        DB::table('team_student')->where('role', 'all_rounder')->update(['role' => 'batting_allrounder']);
        DB::statement("ALTER TABLE team_student MODIFY role ENUM({$new}) NULL");
    }

    public function down(): void
    {
        $all = "'".implode("','", array_unique([...self::OLD, ...self::NEW]))."'";
        $old = "'".implode("','", self::OLD)."'";

        DB::statement("ALTER TABLE students MODIFY playing_role ENUM({$all}) NOT NULL DEFAULT 'batter'");
        DB::table('students')->where('playing_role', 'batter')->update(['playing_role' => 'batsman']);
        DB::table('students')->whereIn('playing_role', ['batting_allrounder', 'bowling_allrounder'])
            ->update(['playing_role' => 'all_rounder']);
        DB::statement("ALTER TABLE students MODIFY playing_role ENUM({$old}) NOT NULL DEFAULT 'batsman'");

        DB::statement("ALTER TABLE team_student MODIFY role ENUM({$all}) NULL");
        DB::table('team_student')->where('role', 'batter')->update(['role' => 'batsman']);
        DB::table('team_student')->whereIn('role', ['batting_allrounder', 'bowling_allrounder'])
            ->update(['role' => 'all_rounder']);
        DB::statement("ALTER TABLE team_student MODIFY role ENUM({$old}) NULL");
    }
};
