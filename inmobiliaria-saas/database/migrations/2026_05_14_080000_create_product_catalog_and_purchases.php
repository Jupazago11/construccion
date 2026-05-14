<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('product_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('product_group_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign(['product_group_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('product_groups')
                ->restrictOnDelete();
            $table->unique(['product_group_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->unique(['id', 'product_group_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('product_group_id');
            $table->foreignId('product_subgroup_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign(['product_group_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('product_groups')
                ->restrictOnDelete();
            $table->foreign(['product_subgroup_id', 'product_group_id'])
                ->references(['id', 'product_group_id'])
                ->on('product_subgroups')
                ->restrictOnDelete();
            $table->unique(['product_subgroup_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('provider_id');
            }

            if (! Schema::hasColumn('expenses', 'quantity')) {
                $table->string('quantity')->nullable()->after('subtotal_amount');
            }
        });

        DB::statement('ALTER TABLE expenses ALTER COLUMN category_id DROP NOT NULL');
        DB::statement('ALTER TABLE expenses ALTER COLUMN subcategory_id DROP NOT NULL');

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign(['product_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('products')
                ->restrictOnDelete();
            $table->index(['company_id', 'product_id']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('project_id');
            $table->foreignId('provider_id');
            $table->foreignId('product_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('purchase_date');
            $table->text('description')->nullable();
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->string('quantity')->nullable();
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign(['project_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('projects')
                ->restrictOnDelete();
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers')
                ->restrictOnDelete();
            $table->foreign(['product_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('products')
                ->restrictOnDelete();
            $table->index(['company_id', 'project_id', 'purchase_date']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'product_id')) {
                $table->dropForeign(['product_id', 'company_id']);
                $table->dropIndex(['company_id', 'product_id']);
                $table->dropColumn('product_id');
            }

            if (Schema::hasColumn('expenses', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });

        Schema::dropIfExists('products');
        Schema::dropIfExists('product_subgroups');
        Schema::dropIfExists('product_groups');
    }
};
