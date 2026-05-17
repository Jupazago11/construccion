<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset2_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset2_id')
                ->constrained('assets2')
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('disk')->default('r2');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('media_type', 20)->default('image');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['asset2_id', 'status']);
            $table->index(['asset2_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset2_media');
    }
};
