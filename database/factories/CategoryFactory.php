<?php

namespace Database\Factories;

use App\Http\Controllers\Api\CategoryController;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Category::class;
    public function definition()
    {
        $catTitle = $this->faker->words(1, true);
        $url = Str::slug($catTitle);
        $createdUser = $this->faker->name();
        return [
            'cat_title' => $catTitle,
            'url' => $url,
            'created_at'=>$this->faker->dateTimeBetween('-3 years','3 weeks'),
            'updated_at'=>$this->faker->dateTimeBetween('-2 weeks','now'),
            'created_by' => $createdUser,
            'updated_by' => $createdUser,
        ];
    }
}
