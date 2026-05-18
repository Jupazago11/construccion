<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('asset_condition');
        });

        Schema::table('assets2', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('asset_condition');
        });

        DB::table('assets')->whereNull('quantity')->update(['quantity' => 1]);
        DB::table('assets2')->whereNull('quantity')->update(['quantity' => 1]);
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('assets2', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
