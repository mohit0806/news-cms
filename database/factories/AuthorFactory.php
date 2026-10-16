<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Author;
use App\Support\Slugger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name' => $name,
            'slug' => Slugger::slug($name.' '.$this->faker->unique()->randomNumber()),
            'bio' => $this->faker->sentence(),
            'email' => $this->faker->unique()->safeEmail(),
            'is_active' => true,
        ];
    }
}
