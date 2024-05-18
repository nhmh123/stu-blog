<?php

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'comment_content'=>$this->faker->paragraph(),
            'user_id'=>rand(1,50),
            'post_id'=>rand(1,80),
            'created_at'=>$this->faker->dateTimeBetween('-1 weeks','+3 weeks'),
            'parent_comment_id'=>rand(1,40),
        ];
    }
}
