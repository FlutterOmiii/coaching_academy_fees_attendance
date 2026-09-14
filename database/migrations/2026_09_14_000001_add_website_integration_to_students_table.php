<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integration columns for admissions pushed in from the public website.
 *
 * Every column is nullable so existing students — all of them created by hand
 * in the admin panel — stay valid and untouched. website_admission_uuid is the
 * idempotency key: a unique index guarantees one website admission can never
 * become two CRM students, even under a concurrent retry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->uuid('website_admission_uuid')->nullable()->after('notes');
            $table->string('admission_source', 20)->nullable()->after('website_admission_uuid');
            $table->string('source_reference', 100)->nullable()->after('admission_source');
            $table->timestamp('synced_at')->nullable()->after('source_reference');

            $table->unique('website_admission_uuid');
            $table->index('admission_source');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['website_admission_uuid']);
            $table->dropIndex(['admission_source']);
            $table->dropColumn(['website_admission_uuid', 'admission_source', 'source_reference', 'synced_at']);
        });
    }
};
