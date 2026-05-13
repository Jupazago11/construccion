<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('company_id');
            });

            DB::table('users')
                ->select(['id', 'name', 'email'])
                ->orderBy('id')
                ->get()
                ->each(function ($user): void {
                    $baseUsername = Str::of($user->email ?: $user->name)
                        ->lower()
                        ->before('@')
                        ->replaceMatches('/[^a-z0-9._-]/', '')
                        ->trim('._-')
                        ->value();

                    $baseUsername = $baseUsername !== '' ? $baseUsername : 'user';
                    $username = $baseUsername;
                    $suffix = 1;

                    while (
                        DB::table('users')
                            ->where('username', $username)
                            ->where('id', '!=', $user->id)
                            ->exists()
                    ) {
                        $username = "{$baseUsername}{$suffix}";
                        $suffix++;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $username]);
                });
        }

        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')
                    ->default('active')
                    ->after('password');
            });

            DB::table('users')
                ->whereNull('status')
                ->update(['status' => 'active']);
        }

        Schema::table('users', function (Blueprint $table) {
            if (! $this->indexExists('users', 'users_username_unique')) {
                $table->unique('username');
            }

            if (! $this->indexExists('users', 'users_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_company_id_status_index')) {
                $table->dropIndex('users_company_id_status_index');
            }

            if ($this->indexExists('users', 'users_username_unique')) {
                $table->dropUnique('users_username_unique');
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
