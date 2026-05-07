<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('project_id');

            $table->foreignId('category_id');

            $table->foreignId('subcategory_id');

            $table->foreignId('auxiliary_id')->nullable();

            $table->foreignId('provider_id')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('expense_number');
            $table->date('expense_date');

            $table->string('payment_method')->nullable();
            // cash, bank_transfer, credit_card, debit_card, other

            $table->text('description');

            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);

            $table->string('status')->default('active');
            // active, inactive, deleted

            $table->timestamps();

            $table->foreign(['project_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('projects')
                ->restrictOnDelete();

            $table->foreign(['category_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('categories')
                ->restrictOnDelete();

            $table->foreign(['subcategory_id', 'category_id'])
                ->references(['id', 'category_id'])
                ->on('subcategories')
                ->restrictOnDelete();

            $table->foreign(['auxiliary_id', 'subcategory_id'])
                ->references(['id', 'subcategory_id'])
                ->on('auxiliaries')
                ->restrictOnDelete();

            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers')
                ->restrictOnDelete();

            $table->unique(['project_id', 'expense_number']);
            $table->index(['company_id', 'project_id', 'expense_date']);
            $table->index(['category_id', 'subcategory_id', 'auxiliary_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
