<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('nit')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('logo_path')->nullable();
            $table->string('primary_color')->nullable();

            $table->string('status')->default('active');
            // active, inactive, deleted

            $table->timestamps();

            $table->unique('nit');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
