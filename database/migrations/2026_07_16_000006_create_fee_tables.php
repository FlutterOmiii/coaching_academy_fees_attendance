<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->cascadeOnDelete();
            $table->enum('type', ['admission', 'tuition', 'kit', 'tournament', 'equipment', 'other'])
                ->default('tuition');
            $table->enum('frequency', ['one_time', 'monthly', 'quarterly', 'half_yearly', 'annual'])
                ->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['status', 'type']);
        });

        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 30)->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();

            // First day of the period being billed, e.g. 2026-07-01 for July 2026.
            $table->date('billing_period');

            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            // Kept in sync by the database so pending-fee reporting cannot drift.
            $table->decimal('balance_amount', 10, 2)->storedAs('total_amount - paid_amount');

            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // One invoice per student per structure per billing period.
            $table->unique(['student_id', 'fee_structure_id', 'billing_period'], 'fee_invoice_period_unique');
            $table->index(['status', 'due_date']);
            $table->index('billing_period');
        });

        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 30)->unique();
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('mode', ['cash', 'upi', 'card', 'net_banking', 'cheque', 'bank_transfer'])->default('cash');
            $table->string('reference_no', 100)->nullable();
            $table->enum('status', ['completed', 'refunded', 'failed'])->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['payment_date', 'status']);
            $table->index('student_id');
        });

        Schema::create('fee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'call'])->default('email');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['fee_invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_invoices');
        Schema::dropIfExists('fee_structures');
    }
};
