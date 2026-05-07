<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->string('status')->default('active');
            // active, inactive, deleted

            $table->timestamps();

            $table->unique(['category_id', 'name']);
            $table->unique(['id', 'category_id']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategories');
    }
};
