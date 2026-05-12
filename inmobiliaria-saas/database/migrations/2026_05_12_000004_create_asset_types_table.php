<?php

use App\Enums\EntityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default(EntityStatus::Active->value);
            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('asset_type_id')
                ->nullable()
                ->after('asset_type')
                ->constrained('asset_types')
                ->nullOnDelete();

            $table->index(['company_id', 'asset_type_id']);
        });

        $labels = [
            'tool' => 'Herramienta',
            'equipment' => 'Equipo',
        ];

        $assets = DB::table('assets')
            ->select('company_id', 'asset_type')
            ->whereNotNull('asset_type')
            ->distinct()
            ->get();

        foreach ($assets as $asset) {
            $name = $labels[$asset->asset_type] ?? Str::headline((string) $asset->asset_type);

            $typeId = DB::table('asset_types')->insertGetId([
                'company_id' => $asset->company_id,
                'name' => $name,
                'status' => EntityStatus::Active->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('assets')
                ->where('company_id', $asset->company_id)
                ->where('asset_type', $asset->asset_type)
                ->update(['asset_type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_type_id');
        });

        Schema::dropIfExists('asset_types');
    }
};
