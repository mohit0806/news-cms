<?php

declare(strict_types=1);

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
        Schema::create('layout_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('region', 50)->default('home');
            $table->char('language', 5);
            $table->string('widget', 50);
            $table->string('title')->nullable();
            $table->json('config_json')->nullable();
            $table->integer('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_blocks');
    }
};
