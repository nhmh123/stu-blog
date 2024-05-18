<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $postTitle=$this->faker->sentence();
        $url = Str::slug($postTitle);
        $createdUser = $this->faker->name();
        return [
            'post_title'=>$postTitle,
            'post_content'=>$this->faker->paragraph(),
            'post_thumbnail'=>$this->faker->imageUrl(640,480,'animals',true),
            'url'=>$url,
            'created_at'=>$this->faker->dateTimeBetween('-3 years','3 weeks'),
            'updated_at'=>$this->faker->dateTimeBetween('-2 weeks','now'),
            'created_by'=>$createdUser,
            'updated_by'=>$createdUser,
            'cat_id'=>rand(1,10),
            'post_thumbnail_public_id'=>null,
        ];
    }
}
