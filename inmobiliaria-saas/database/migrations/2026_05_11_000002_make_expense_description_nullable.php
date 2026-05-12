<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE expenses ALTER COLUMN description DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE expenses SET description = '' WHERE description IS NULL");
        DB::statement('ALTER TABLE expenses ALTER COLUMN description SET NOT NULL');
    }
};
