<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // providers2 needs a (id, company_id) unique to support compound FKs
        Schema::table('providers2', function (Blueprint $table) {
            $table->unique(['id', 'company_id']);
        });

        // --- expenses ---
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['provider_id', 'company_id']);
        });
        DB::statement('UPDATE expenses SET provider_id = NULL');
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers2')
                ->nullOnDelete();
        });

        // --- purchases (provider_id was NOT NULL, make nullable first) ---
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['provider_id', 'company_id']);
            $table->unsignedBigInteger('provider_id')->nullable()->change();
        });
        DB::statement('UPDATE purchases SET provider_id = NULL');
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers2')
                ->nullOnDelete();
        });

        // --- invoices (provider_id was NOT NULL, make nullable first) ---
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['provider_id', 'company_id']);
            $table->unsignedBigInteger('provider_id')->nullable()->change();
        });
        DB::statement('UPDATE invoices SET provider_id = NULL');
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers2')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // --- invoices ---
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['provider_id', 'company_id']);
        });
        DB::statement('UPDATE invoices SET provider_id = NULL');
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers')
                ->nullOnDelete();
        });

        // --- purchases ---
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['provider_id', 'company_id']);
        });
        DB::statement('UPDATE purchases SET provider_id = NULL');
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers')
                ->nullOnDelete();
        });

        // --- expenses ---
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['provider_id', 'company_id']);
        });
        DB::statement('UPDATE expenses SET provider_id = NULL');
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign(['provider_id', 'company_id'])
                ->references(['id', 'company_id'])
                ->on('providers')
                ->nullOnDelete();
        });

        Schema::table('providers2', function (Blueprint $table) {
            $table->dropUnique(['id', 'company_id']);
        });
    }
};
