<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addMissingColumns();
        $this->addMissingForeignKeys();
        $this->addMissingIndexes();
    }

    public function down(): void
    {
        // Corrective migration for inconsistent environments.
    }

    private function addMissingColumns(): void
    {
        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->uuid('batch_uuid')->nullable();
            });
        }

        if (! Schema::hasColumn('activity_log', 'company_id')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->foreignId('company_id')->nullable();
            });
        }

        if (! Schema::hasColumn('activity_log', 'project_id')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->foreignId('project_id')->nullable();
            });
        }

        if (! Schema::hasColumn('activity_log', 'reverted_by')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->foreignId('reverted_by')->nullable();
            });
        }

        if (! Schema::hasColumn('activity_log', 'reverted_at')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->timestamp('reverted_at')->nullable();
            });
        }

        if (! Schema::hasColumn('activity_log', 'expires_at')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->timestamp('expires_at')->nullable();
            });
        }
    }

    private function addMissingForeignKeys(): void
    {
        if (Schema::hasColumn('activity_log', 'company_id') && ! $this->constraintExists('activity_log', 'activity_log_company_id_foreign')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('activity_log', 'project_id') && ! $this->constraintExists('activity_log', 'activity_log_project_id_foreign')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('activity_log', 'reverted_by') && ! $this->constraintExists('activity_log', 'activity_log_reverted_by_foreign')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->foreign('reverted_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    private function addMissingIndexes(): void
    {
        if (Schema::hasColumn('activity_log', 'batch_uuid') && ! $this->indexExists('activity_log_batch_uuid_index')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->index('batch_uuid');
            });
        }

        if (Schema::hasColumn('activity_log', 'expires_at') && ! $this->indexExists('activity_log_expires_at_index')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->index('expires_at');
            });
        }

        if (
            Schema::hasColumn('activity_log', 'company_id')
            && Schema::hasColumn('activity_log', 'created_at')
            && ! $this->indexExists('activity_log_company_id_created_at_index')
        ) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->index(['company_id', 'created_at']);
            });
        }

        if (
            Schema::hasColumn('activity_log', 'project_id')
            && Schema::hasColumn('activity_log', 'created_at')
            && ! $this->indexExists('activity_log_project_id_created_at_index')
        ) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->index(['project_id', 'created_at']);
            });
        }
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        return match (DB::getDriverName()) {
            'pgsql' => DB::table('information_schema.table_constraints')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('constraint_name', $constraint)
                ->exists(),
            'mysql', 'mariadb' => DB::table('information_schema.table_constraints')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('constraint_name', $constraint)
                ->exists(),
            'sqlite' => true,
            default => false,
        };
    }

    private function indexExists(string $index): bool
    {
        try {
            foreach (Schema::getIndexes('activity_log') as $currentIndex) {
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
                ->where('indexname', $index)
                ->exists(),
            'mysql', 'mariadb' => DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'activity_log')
                ->where('index_name', $index)
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('activity_log')"))
                ->contains(fn ($currentIndex): bool => ($currentIndex->name ?? null) === $index),
            default => false,
        };
    }
};
