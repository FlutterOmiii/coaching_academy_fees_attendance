<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The owner's private spending. Deliberately a separate table from
     * `expenses` so personal outgoings can never leak into the business
     * accounts, analytics or the accountant's view.
     */
    public function up(): void
    {
        Schema::create('personal_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('spent_on');
            $table->string('category', 50)->default('Other');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['admin_id', 'spent_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_expenses');
    }
};
