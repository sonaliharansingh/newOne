<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RelationshipRuleSeeder::class,
            HotelSeeder::class,
        ]);

        User::factory()->admin()->create([
            'name' => 'Test Admin',
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
        ]);
    }
}
