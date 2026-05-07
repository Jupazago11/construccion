<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete();

            $table->string('status')->default('active');
            // active, inactive, deleted
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'module_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_modules');
    }
};
