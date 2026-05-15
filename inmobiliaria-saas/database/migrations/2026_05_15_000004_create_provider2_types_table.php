<?php

use App\Enums\EntityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider2_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default(EntityStatus::Active->value);
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::table('providers2', function (Blueprint $table) {
            $table->foreignId('provider2_type_id')
                ->nullable()
                ->after('company_id')
                ->constrained('provider2_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('providers2', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider2_type_id');
        });

        Schema::dropIfExists('provider2_types');
    }
};
