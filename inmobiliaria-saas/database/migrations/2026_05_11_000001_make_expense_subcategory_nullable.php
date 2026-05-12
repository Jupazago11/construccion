<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['auxiliary_id', 'subcategory_id']);
            $table->dropForeign(['subcategory_id', 'category_id']);
        });

        DB::statement('ALTER TABLE expenses ALTER COLUMN subcategory_id DROP NOT NULL');

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign(['subcategory_id', 'category_id'])
                ->references(['id', 'category_id'])
                ->on('subcategories')
                ->restrictOnDelete();

            $table->foreign(['auxiliary_id', 'subcategory_id'])
                ->references(['id', 'subcategory_id'])
                ->on('auxiliaries')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['auxiliary_id', 'subcategory_id']);
            $table->dropForeign(['subcategory_id', 'category_id']);
        });

        DB::statement('ALTER TABLE expenses ALTER COLUMN subcategory_id SET NOT NULL');

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign(['subcategory_id', 'category_id'])
                ->references(['id', 'category_id'])
                ->on('subcategories')
                ->restrictOnDelete();

            $table->foreign(['auxiliary_id', 'subcategory_id'])
                ->references(['id', 'subcategory_id'])
                ->on('auxiliaries')
                ->restrictOnDelete();
        });
    }
};
