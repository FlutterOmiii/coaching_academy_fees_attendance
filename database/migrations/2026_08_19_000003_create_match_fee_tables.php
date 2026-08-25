<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Match Fees: one-off matches with a per-student fee, tracked inside the
 * Fees module. Deliberately separate from the monthly invoice pipeline
 * (fee_invoices / fee_payments) so monthly analytics stay untouched, and
 * from the parked cricket_matches module which is tied to teams/tournaments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_matches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('match_date');
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->decimal('fee_amount', 10, 2);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('match_date');
        });

        Schema::create('match_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_match_id')->constrained('fee_matches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->enum('mode', ['cash', 'upi', 'card', 'net_banking', 'cheque', 'bank_transfer'])->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('receipt_no', 30)->nullable()->unique();
            $table->string('notes')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // A student appears once per match.
            $table->unique(['fee_match_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_fees');
        Schema::dropIfExists('fee_matches');
    }
};
