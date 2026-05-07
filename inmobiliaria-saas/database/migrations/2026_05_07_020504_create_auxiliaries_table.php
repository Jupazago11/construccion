<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auxiliaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subcategory_id')
                ->constrained('subcategories')
                ->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->string('status')->default('active');
            // active, inactive, deleted

            $table->timestamps();

            $table->unique(['subcategory_id', 'name']);
            $table->unique(['id', 'subcategory_id']);
            $table->index(['subcategory_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auxiliaries');
    }
};
