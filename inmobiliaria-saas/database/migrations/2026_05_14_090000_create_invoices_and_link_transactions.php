<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('project_id');
            $table->foreignId('provider_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date');
            $table->text('description')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->foreign(['project_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('projects')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers')
                ->restrictOnDelete();
            $table->index(['company_id', 'project_id', 'provider_id', 'type', 'status'], 'invoices_scope_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('provider_id')->constrained('invoices')->nullOnDelete();
            $table->index(['company_id', 'invoice_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('provider_id')->constrained('invoices')->nullOnDelete();
            $table->index(['company_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['company_id', 'invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['company_id', 'invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::dropIfExists('invoices');
    }
};
