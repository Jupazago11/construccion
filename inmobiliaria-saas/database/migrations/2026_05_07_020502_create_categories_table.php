<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->string('status')->default('active');
            // active, inactive, deleted

            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->unique(['id', 'project_id']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
