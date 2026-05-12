<?php

use App\Enums\EntityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_novelty_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->boolean('adds_value')->default(false);
            $table->string('status')->default(EntityStatus::Active->value);
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'name']);
        });

        Schema::table('asset_novelties', function (Blueprint $table) {
            $table->foreignId('asset_novelty_type_id')
                ->nullable()
                ->after('created_by')
                ->constrained('asset_novelty_types')
                ->nullOnDelete();

            $table->index(['asset_id', 'asset_novelty_type_id'], 'asset_novelties_asset_type_index');
        });

        $companies = DB::table('assets')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            $typeId = DB::table('asset_novelty_types')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Mantenimiento',
                'adds_value' => false,
                'status' => EntityStatus::Active->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('asset_novelties')
                ->whereIn('asset_id', DB::table('assets')->select('id')->where('company_id', $companyId))
                ->whereNull('asset_novelty_type_id')
                ->update(['asset_novelty_type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        Schema::table('asset_novelties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_novelty_type_id');
        });

        Schema::dropIfExists('asset_novelty_types');
    }
};
