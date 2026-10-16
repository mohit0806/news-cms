<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NewsCMS-owned folder structures (ADR-0001). The folder↔media link never
     * touches package-owned columns; `media_id` carries no foreign key.
     */
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('media_folder_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('media_folders')->cascadeOnDelete();
            $table->unsignedBigInteger('media_id');
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->unique(['folder_id', 'media_id']);
            $table->index('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_folder_media');
        Schema::dropIfExists('media_folders');
    }
};
