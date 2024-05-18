<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $username = $this->faker->name();
        $url = Str::slug($username);
        return [
            'username' => $username,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make("Abc123@"), // password
            // 'remember_token' => Str::random(10),
            'remember_token' => null,
            'phone' => $this->faker->numerify('##########'),
            'avatar' => null,
            'avatar_public_id' => null,
            'url' => $url,
            'created_at'=>$this->faker->dateTimeBetween('-3 years','3 weeks'),
            'updated_at'=>$this->faker->dateTimeBetween('-2 weeks','now'),
            'role_id' => rand(1, 2),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
