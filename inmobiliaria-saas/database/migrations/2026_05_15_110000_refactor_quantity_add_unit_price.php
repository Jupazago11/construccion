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
            $table->dropColumn('quantity');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('unit_price', 14, 4)->default(0)->after('subtotal_amount');
            $table->decimal('quantity', 10, 2)->nullable()->after('unit_price');
        });

        DB::statement('UPDATE expenses SET unit_price = subtotal_amount');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('unit_price', 14, 4)->default(0)->after('subtotal_amount');
            $table->decimal('quantity', 10, 2)->nullable()->after('unit_price');
        });

        DB::statement('UPDATE purchases SET unit_price = subtotal_amount');
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'quantity']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('quantity')->nullable()->after('subtotal_amount');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'quantity']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('quantity')->nullable()->after('subtotal_amount');
        });
    }
};
