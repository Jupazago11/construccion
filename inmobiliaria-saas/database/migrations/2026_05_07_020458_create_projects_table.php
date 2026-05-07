<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->string('name');
            $table->string('project_type')->nullable();
            // apartments, houses, lots, mixed, other

            $table->text('description')->nullable();

            $table->string('country')->default('Colombia');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('location_reference')->nullable();

            $table->date('start_date')->nullable();

            $table->string('status')->default('planning');
            // planning, active, paused, completed, cancelled, deleted

            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['id', 'company_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
