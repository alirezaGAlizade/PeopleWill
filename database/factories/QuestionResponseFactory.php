<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionResponse>
 */
class QuestionResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'sequence' => 1,
        ];
    }
}
