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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('vocabulary', 20);
            $table->foreignId('parent_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->integer('sort')->default(0);
            $table->string('color', 7)->nullable();
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('term_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('language', 5);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['language', 'slug']);
        });

        Schema::create('termables', function (Blueprint $table) {
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->morphs('termable');
            $table->integer('sort')->default(0);

            $table->unique(['term_id', 'termable_type', 'termable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('termables');
        Schema::dropIfExists('term_translations');
        Schema::dropIfExists('terms');
    }
};
