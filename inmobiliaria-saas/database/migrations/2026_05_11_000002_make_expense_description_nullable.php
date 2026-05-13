<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('expenses', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE expenses ALTER COLUMN description DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE expenses SET description = '' WHERE description IS NULL");

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('expenses', function (Blueprint $table) {
                $table->text('description')->nullable(false)->change();
            });

            return;
        }

        DB::statement('ALTER TABLE expenses ALTER COLUMN description SET NOT NULL');
    }
};
