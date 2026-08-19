<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Set only on salary payments: which coach was paid, and for which
            // month. Keeps salary history queryable while the money still lives
            // in the ordinary expense book (analytics need no changes).
            $table->foreignId('coach_id')->nullable()->after('expense_category_id')
                ->constrained('coaches')->nullOnDelete();
            $table->date('salary_month')->nullable()->after('coach_id');

            $table->index(['coach_id', 'salary_month']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coach_id');
            $table->dropColumn('salary_month');
        });
    }
};
