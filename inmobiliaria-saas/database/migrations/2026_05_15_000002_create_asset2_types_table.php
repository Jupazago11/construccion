<?php

use App\Enums\EntityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset2_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default(EntityStatus::Active->value);
            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('assets2', function (Blueprint $table) {
            $table->foreignId('asset2_type_id')
                ->nullable()
                ->after('asset2_type')
                ->constrained('asset2_types')
                ->nullOnDelete();

            $table->index(['company_id', 'asset2_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('assets2', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset2_type_id');
        });

        Schema::dropIfExists('asset2_types');
    }
};
