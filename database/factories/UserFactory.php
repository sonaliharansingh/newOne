<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'language' => 'English',
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->name('female'),
            'phone' => fake()->numerify('##########'),
            'city' => fake()->city(),
            'area' => fake()->streetName(),
            'state' => fake()->state(),
            'country' => 'India',
            'address' => fake()->address(),
            'gender' => fake()->randomElement(['male', 'female']),
            'adhar_id' => fake()->unique()->numerify('############'),
            'luggage_count' => fake()->numberBetween(0, 3),
            'type' => 'solo',
            'role' => 'User',
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => ['gender' => 'male']);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => ['gender' => 'female']);
    }

    public function withAge(int $age): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => now()->subYears($age)->subDays(fake()->numberBetween(0, 364))->format('Y-m-d'),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }
}
