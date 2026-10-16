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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->char('language', 5);
            $table->uuid('translation_group_id')->nullable();
            $table->string('title', 200);
            $table->string('slug', 190);
            $table->longText('body_json')->nullable();
            $table->longText('body_html')->nullable();
            $table->enum('template', ['default', 'full-width'])->default('default');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->boolean('allow_indexing')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['language', 'slug']);
            $table->index('translation_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
