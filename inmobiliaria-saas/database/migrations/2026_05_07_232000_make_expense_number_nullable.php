<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('expenses', 'expense_number')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->string('expense_number')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('expenses', 'expense_number')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->string('expense_number')->nullable(false)->change();
            });
        }
    }
};
