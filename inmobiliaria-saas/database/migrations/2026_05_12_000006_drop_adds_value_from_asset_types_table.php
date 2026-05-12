<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('asset_types', 'adds_value')) {
            return;
        }

        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropColumn('adds_value');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('asset_types', 'adds_value')) {
            return;
        }

        Schema::table('asset_types', function (Blueprint $table) {
            $table->boolean('adds_value')->default(true)->after('name');
        });
    }
};
