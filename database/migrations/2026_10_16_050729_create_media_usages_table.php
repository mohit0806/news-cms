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
     * NewsCMS-owned usage index (ADR-0001). `media_id` carries no foreign key:
     * the package owns its table and key shape, which is verified at T05.1.
     * Folder tables land in T05.2.
     */
    public function up(): void
    {
        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            $table->timestamps();

            $table->unique(['media_id', 'usable_type', 'usable_id'], 'media_usages_unique');
            $table->index(['usable_type', 'usable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_usages');
    }
};
