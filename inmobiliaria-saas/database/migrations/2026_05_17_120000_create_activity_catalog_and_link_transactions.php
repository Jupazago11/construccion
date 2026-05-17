<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('activity_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('activity_group_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign(['activity_group_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('activity_groups')
                ->restrictOnDelete();
            $table->unique(['activity_group_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->unique(['id', 'activity_group_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('activity_group_id');
            $table->foreignId('activity_subgroup_id');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign(['activity_group_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('activity_groups')
                ->restrictOnDelete();
            $table->foreign(['activity_subgroup_id', 'activity_group_id'])
                ->references(['id', 'activity_group_id'])
                ->on('activity_subgroups')
                ->restrictOnDelete();
            $table->unique(['activity_subgroup_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('product_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign(['activity_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('activities')
                ->restrictOnDelete();
            $table->index(['company_id', 'activity_id']);
        });

        DB::statement('ALTER TABLE purchases ALTER COLUMN product_id DROP NOT NULL');

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('product_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign(['activity_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('activities')
                ->restrictOnDelete();
            $table->index(['company_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['activity_id', 'company_id']);
            $table->dropIndex(['company_id', 'activity_id']);
            $table->dropColumn('activity_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['activity_id', 'company_id']);
            $table->dropIndex(['company_id', 'activity_id']);
            $table->dropColumn('activity_id');
        });

        Schema::dropIfExists('activities');
        Schema::dropIfExists('activity_subgroups');
        Schema::dropIfExists('activity_groups');
    }
};
