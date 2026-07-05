<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_records', function (Blueprint $table) {
            $table->id();
            $table->date('record_date');
            $table->string('category');
            $table->string('concept');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_records');
    }
};
