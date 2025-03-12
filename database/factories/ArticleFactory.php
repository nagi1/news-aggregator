<?php

namespace Database\Factories;

use App\Enums\NewsProviderEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
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
        return [
            'slug' => $this->faker->slug(),
            'title' => $this->faker->sentence(),
            'api_provider' => NewsProviderEnum::NEWS_API,
            'source' => $this->faker->company(),
            'author' => $this->faker->name(),
            'category' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'url' => $this->faker->url(),
            'image' => $this->faker->imageUrl(),
            'published_at' => $this->faker->dateTime(),
        ];
    }
}
