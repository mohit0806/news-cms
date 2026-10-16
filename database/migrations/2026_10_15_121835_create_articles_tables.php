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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['standard', 'live'])->default('standard');
            $table->char('language', 5);
            $table->uuid('translation_group_id')->nullable();
            $table->string('title', 200);
            $table->string('subtitle', 250)->nullable();
            $table->string('slug', 190);
            $table->text('summary')->nullable();
            $table->longText('body_json')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('featured_caption')->nullable();
            $table->string('featured_credit')->nullable();
            $table->foreignId('primary_category_id')->constrained('terms');
            $table->foreignId('author_id')->constrained('authors');
            $table->string('dateline', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->enum('status', ['draft', 'in_review', 'scheduled', 'published', 'unpublished'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_breaking')->default(false);
            $table->dateTime('breaking_until')->nullable();
            $table->enum('live_status', ['live', 'ended'])->nullable();
            $table->boolean('allow_indexing')->default(true);
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->string('canonical_url')->nullable();
            $table->unsignedSmallInteger('reading_time_min')->default(1);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->text('review_note')->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['language', 'status', 'published_at']);
            $table->index(['primary_category_id', 'status', 'published_at']);
            $table->index(['is_breaking', 'breaking_until']);
            $table->unique(['language', 'slug']);
            $table->index('translation_group_id');
        });

        Schema::create('article_authors', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->integer('sort')->default(0);

            $table->unique(['article_id', 'author_id']);
        });

        Schema::create('article_related', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_article_id')->constrained('articles')->cascadeOnDelete();
            $table->integer('sort')->default(0);

            $table->unique(['article_id', 'related_article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_related');
        Schema::dropIfExists('article_authors');
        Schema::dropIfExists('articles');
    }
};
