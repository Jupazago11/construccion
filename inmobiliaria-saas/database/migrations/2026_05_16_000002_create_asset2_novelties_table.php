<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset2_novelties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset2_id')->constrained('assets2')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_novelty_type_id')
                ->nullable()
                ->constrained('asset_novelty_types')
                ->nullOnDelete();
            $table->decimal('cost', 14, 2)->default(0);
            $table->text('description');
            $table->string('asset_status');
            $table->date('novelty_date');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['asset2_id', 'status']);
            $table->index(['asset2_id', 'novelty_date']);
            $table->index(['asset2_id', 'asset_novelty_type_id'], 'asset2_novelties_asset_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset2_novelties');
    }
};
