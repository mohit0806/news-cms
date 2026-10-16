<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\Author;
use App\Models\Term;
use App\Models\User;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'language' => 'en',
            'title' => $title,
            'slug' => Slugger::slug($title.' '.$this->faker->unique()->randomNumber()),
            'summary' => $this->faker->sentence(12),
            'body_json' => [
                'schema' => 1,
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $this->faker->paragraph()]]],
                ],
            ],
            'body_text' => $this->faker->paragraph(),
            'body_html' => '<p class="cms-block cms-block--paragraph">Body</p>',
            'primary_category_id' => Term::factory(),
            'author_id' => Author::factory(),
            'status' => 'draft',
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(?string $publishedAt = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => $publishedAt ?? now()->subHour(),
            'body_html' => '<p class="cms-block cms-block--paragraph">Body</p>',
            'body_text' => 'Body',
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'in_review']);
    }

    public function scheduled(string $publishedAt): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'scheduled',
            'published_at' => $publishedAt,
        ]);
    }
}
