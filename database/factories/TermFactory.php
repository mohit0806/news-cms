<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Term;
use App\Models\TermTranslation;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vocabulary' => 'category',
            'sort' => 0,
            'show_in_menu' => false,
            'is_active' => true,
        ];
    }

    /**
     * Attach a translation for the given language.
     */
    public function translated(string $language = 'en', ?string $name = null): static
    {
        $name ??= ucfirst((string) $this->faker->unique()->word());

        return $this->afterCreating(function (Term $term) use ($language, $name): void {
            $term->translations()->create([
                'language' => $language,
                'name' => $name,
                'slug' => Slugger::unique(
                    Slugger::slug($name),
                    fn (string $candidate): bool => TermTranslation::query()
                        ->where('language', $language)
                        ->where('slug', $candidate)
                        ->exists()
                ),
            ]);
        });
    }

    /**
     * Define a tag term.
     */
    public function tag(): static
    {
        return $this->state(fn (array $attributes): array => ['vocabulary' => 'tag', 'parent_id' => null]);
    }
}
