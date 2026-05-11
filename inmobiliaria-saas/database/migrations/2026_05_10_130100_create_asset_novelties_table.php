<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_novelties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('cost', 14, 2)->default(0);
            $table->text('description');
            $table->string('asset_status');
            $table->date('novelty_date');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['asset_id', 'status']);
            $table->index(['asset_id', 'novelty_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_novelties');
    }
};
