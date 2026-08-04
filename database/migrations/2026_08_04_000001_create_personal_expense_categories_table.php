<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-scoped categories for the private personal ledger. Kept separate from
 * the business `expense_categories` table so personal and business books never
 * share data. Each admin owns their own list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('icon', 16)->default('📌');
            $table->timestamps();

            // A person can't have two categories with the same name.
            $table->unique(['admin_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_expense_categories');
    }
};
