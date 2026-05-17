<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_novelties', function (Blueprint $table) {
            $table->string('name')->nullable()->after('asset_novelty_type_id');
        });

        Schema::table('asset2_novelties', function (Blueprint $table) {
            $table->string('name')->nullable()->after('asset_novelty_type_id');
        });

        DB::table('asset_novelties')
            ->whereNull('name')
            ->update(['name' => 'Novedad']);

        DB::table('asset2_novelties')
            ->whereNull('name')
            ->update(['name' => 'Novedad']);
    }

    public function down(): void
    {
        Schema::table('asset2_novelties', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('asset_novelties', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
