<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE asset_novelties ALTER COLUMN description DROP NOT NULL');
        DB::statement('ALTER TABLE asset2_novelties ALTER COLUMN description DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE asset_novelties SET description = '' WHERE description IS NULL");
        DB::statement("UPDATE asset2_novelties SET description = '' WHERE description IS NULL");

        DB::statement('ALTER TABLE asset_novelties ALTER COLUMN description SET NOT NULL');
        DB::statement('ALTER TABLE asset2_novelties ALTER COLUMN description SET NOT NULL');
    }
};
