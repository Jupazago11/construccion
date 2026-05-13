<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repairModulesTable();
        $this->repairCompanyModulesTable();
        $this->repairProjectsTable();
        $this->repairCategoriesTable();
        $this->repairSubcategoriesTable();
        $this->repairAuxiliariesTable();
        $this->repairProvidersTable();
        $this->repairExpensesTable();
        $this->repairExpenseAttachmentsTable();
    }

    public function down(): void
    {
        //
    }

    protected function repairModulesTable(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (! Schema::hasColumn('modules', 'key')) {
                $table->string('key')->nullable();
            }

            if (! Schema::hasColumn('modules', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('modules', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('modules', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('modules', function (Blueprint $table) {
            if (! $this->indexExists('modules', 'modules_key_unique')) {
                $table->unique('key');
            }

            if (! $this->indexExists('modules', 'modules_status_index')) {
                $table->index('status');
            }
        });
    }

    protected function repairCompanyModulesTable(): void
    {
        Schema::table('company_modules', function (Blueprint $table) {
            if (! Schema::hasColumn('company_modules', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('company_modules', 'module_id')) {
                $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            }

            if (! Schema::hasColumn('company_modules', 'status')) {
                $table->string('status')->default('active');
            }

            if (! Schema::hasColumn('company_modules', 'enabled_at')) {
                $table->timestamp('enabled_at')->nullable();
            }

            if (! Schema::hasColumn('company_modules', 'disabled_at')) {
                $table->timestamp('disabled_at')->nullable();
            }
        });

        Schema::table('company_modules', function (Blueprint $table) {
            if (! $this->indexExists('company_modules', 'company_modules_company_id_module_id_unique')) {
                $table->unique(['company_id', 'module_id']);
            }

            if (! $this->indexExists('company_modules', 'company_modules_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });
    }

    protected function repairProjectsTable(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('projects', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('projects', 'project_type')) {
                $table->string('project_type')->nullable();
            }

            if (! Schema::hasColumn('projects', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('projects', 'country')) {
                $table->string('country')->default('Colombia');
            }

            if (! Schema::hasColumn('projects', 'state')) {
                $table->string('state')->nullable();
            }

            if (! Schema::hasColumn('projects', 'city')) {
                $table->string('city')->nullable();
            }

            if (! Schema::hasColumn('projects', 'address')) {
                $table->string('address')->nullable();
            }

            if (! Schema::hasColumn('projects', 'location_reference')) {
                $table->string('location_reference')->nullable();
            }

            if (! Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')->nullable();
            }

            if (! Schema::hasColumn('projects', 'status')) {
                $table->string('status')->default('planning');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! $this->indexExists('projects', 'projects_company_id_name_unique')) {
                $table->unique(['company_id', 'name']);
            }

            if (! $this->indexExists('projects', 'projects_id_company_id_unique')) {
                $table->unique(['id', 'company_id']);
            }

            if (! $this->indexExists('projects', 'projects_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });
    }

    protected function repairCategoriesTable(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            }

            if (! Schema::hasColumn('categories', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }

            if (! Schema::hasColumn('categories', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (! $this->indexExists('categories', 'categories_project_id_name_unique')) {
                $table->unique(['project_id', 'name']);
            }

            if (! $this->indexExists('categories', 'categories_id_project_id_unique')) {
                $table->unique(['id', 'project_id']);
            }

            if (! $this->indexExists('categories', 'categories_project_id_status_index')) {
                $table->index(['project_id', 'status']);
            }
        });
    }

    protected function repairSubcategoriesTable(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            if (! Schema::hasColumn('subcategories', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('subcategories', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('subcategories', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('subcategories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }

            if (! Schema::hasColumn('subcategories', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (! $this->indexExists('subcategories', 'subcategories_category_id_name_unique')) {
                $table->unique(['category_id', 'name']);
            }

            if (! $this->indexExists('subcategories', 'subcategories_id_category_id_unique')) {
                $table->unique(['id', 'category_id']);
            }

            if (! $this->indexExists('subcategories', 'subcategories_category_id_status_index')) {
                $table->index(['category_id', 'status']);
            }
        });
    }

    protected function repairAuxiliariesTable(): void
    {
        Schema::table('auxiliaries', function (Blueprint $table) {
            if (! Schema::hasColumn('auxiliaries', 'subcategory_id')) {
                $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            }

            if (! Schema::hasColumn('auxiliaries', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('auxiliaries', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('auxiliaries', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }

            if (! Schema::hasColumn('auxiliaries', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('auxiliaries', function (Blueprint $table) {
            if (! $this->indexExists('auxiliaries', 'auxiliaries_subcategory_id_name_unique')) {
                $table->unique(['subcategory_id', 'name']);
            }

            if (! $this->indexExists('auxiliaries', 'auxiliaries_id_subcategory_id_unique')) {
                $table->unique(['id', 'subcategory_id']);
            }

            if (! $this->indexExists('auxiliaries', 'auxiliaries_subcategory_id_status_index')) {
                $table->index(['subcategory_id', 'status']);
            }
        });
    }

    protected function repairProvidersTable(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (! Schema::hasColumn('providers', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('providers', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('providers', 'document_number')) {
                $table->string('document_number')->nullable();
            }

            if (! Schema::hasColumn('providers', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (! Schema::hasColumn('providers', 'email')) {
                $table->string('email')->nullable();
            }

            if (! Schema::hasColumn('providers', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('providers', function (Blueprint $table) {
            if (! $this->indexExists('providers', 'providers_company_id_name_unique')) {
                $table->unique(['company_id', 'name']);
            }

            if (! $this->indexExists('providers', 'providers_id_company_id_unique')) {
                $table->unique(['id', 'company_id']);
            }

            if (! $this->indexExists('providers', 'providers_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });
    }

    protected function repairExpensesTable(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'subcategory_id')) {
                $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'auxiliary_id')) {
                $table->foreignId('auxiliary_id')->nullable()->constrained('auxiliaries')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'provider_id')) {
                $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('expenses', 'expense_number')) {
                $table->string('expense_number')->nullable();
            }

            if (! Schema::hasColumn('expenses', 'expense_date')) {
                $table->date('expense_date')->nullable();
            }

            if (! Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }

            if (! Schema::hasColumn('expenses', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('expenses', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 14, 2)->default(0);
            }

            if (! Schema::hasColumn('expenses', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0);
            }

            if (! Schema::hasColumn('expenses', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 2)->default(0);
            }

            if (! Schema::hasColumn('expenses', 'total_amount')) {
                $table->decimal('total_amount', 14, 2)->default(0);
            }

            if (! Schema::hasColumn('expenses', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (! $this->indexExists('expenses', 'expenses_project_id_expense_number_unique')) {
                $table->unique(['project_id', 'expense_number']);
            }

            if (! $this->indexExists('expenses', 'expenses_company_id_project_id_expense_date_index')) {
                $table->index(['company_id', 'project_id', 'expense_date']);
            }

            if (! $this->indexExists('expenses', 'expenses_category_id_subcategory_id_auxiliary_id_index')) {
                $table->index(['category_id', 'subcategory_id', 'auxiliary_id']);
            }

            if (! $this->indexExists('expenses', 'expenses_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });
    }

    protected function repairExpenseAttachmentsTable(): void
    {
        Schema::table('expense_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_attachments', 'expense_id')) {
                $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            }

            if (! Schema::hasColumn('expense_attachments', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('expense_attachments', 'disk')) {
                $table->string('disk')->default('r2');
            }

            if (! Schema::hasColumn('expense_attachments', 'path')) {
                $table->string('path')->nullable();
            }

            if (! Schema::hasColumn('expense_attachments', 'original_name')) {
                $table->string('original_name')->nullable();
            }

            if (! Schema::hasColumn('expense_attachments', 'mime_type')) {
                $table->string('mime_type')->nullable();
            }

            if (! Schema::hasColumn('expense_attachments', 'size')) {
                $table->unsignedBigInteger('size')->nullable();
            }

            if (! Schema::hasColumn('expense_attachments', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('expense_attachments', function (Blueprint $table) {
            if (! $this->indexExists('expense_attachments', 'expense_attachments_expense_id_status_index')) {
                $table->index(['expense_id', 'status']);
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $currentIndex) {
                if (($currentIndex['name'] ?? null) === $index) {
                    return true;
                }
            }
        } catch (Throwable) {
            //
        }

        return match (DB::getDriverName()) {
            'pgsql' => DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists(),
            'mysql', 'mariadb' => DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('".str_replace("'", "''", $table)."')"))
                ->contains(fn ($currentIndex): bool => ($currentIndex->name ?? null) === $index),
            default => false,
        };
    }
};
