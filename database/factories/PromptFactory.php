<?php

namespace Database\Factories;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prompt>
 */
class PromptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(rand(3, 7)), '.'),
            'body' => collect(range(1, rand(2, 4)))
                ->map(fn () => fake()->paragraph(rand(3, 6)))
                ->implode("\n\n"),
            'is_public' => fake()->boolean(80),
            'user_id' => User::factory()->admin(),
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_public' => true]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }
}
