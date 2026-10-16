<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use App\Models\User;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'language' => 'en',
            'title' => $title,
            'slug' => Slugger::slug($title.' '.$this->faker->unique()->randomNumber()),
            'status' => 'draft',
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'published']);
    }
}
