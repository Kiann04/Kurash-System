<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Define age categories with counts to sum up to 50 per gender
        $categories = [
            ['min' => 4, 'max' => 7, 'count' => 8],    // Kids 4-7
            ['min' => 8, 'max' => 11, 'count' => 8],   // Kids 8-11
            ['min' => 12, 'max' => 13, 'count' => 8],  // Kids 12-13
            ['min' => 14, 'max' => 15, 'count' => 8],  // Cadets 14-15
            ['min' => 16, 'max' => 17, 'count' => 8],  // Juniors 16-17
            ['min' => 18, 'max' => 35, 'count' => 10], // Seniors 18+ (using 35 as reasonable max for seeding)
        ];

        $genders = ['Male', 'Female'];

        foreach ($genders as $gender) {
            foreach ($categories as $category) {
                for ($i = 0; $i < $category['count']; $i++) {
                    
                    // Generate a random birth date within the age range
                    $age = rand($category['min'], $category['max']);
                    
                    // Calculate birthday range for this age
                    // Earliest birthday: Today - (Age + 1) years + 1 day
                    // Latest birthday: Today - Age years
                    $startDate = Carbon::now()->subYears($age + 1)->addDay();
                    $endDate = Carbon::now()->subYears($age);
                    
                    $birthday = Carbon::createFromTimestamp(rand($startDate->timestamp, $endDate->timestamp));

                    Player::create([
                        'full_name' => $faker->firstName($gender == 'Male' ? 'male' : 'female') . ' ' . $faker->lastName,
                        'gender' => $gender,
                        'birthday' => $birthday,
                        'club' => $faker->city . ' Judo Club',
                        'address' => $faker->address,
                        'email' => $faker->unique()->safeEmail,
                        'emergency_contact' => $faker->name,
                        'emergency_contact_number' => $faker->phoneNumber,
                        'registered_at' => Carbon::now()->subMonths(rand(1, 12)),
                        'membership_expires_at' => Carbon::now()->addMonths(rand(1, 12)),
                        'status' => 'active',
                    ]);
                }
            }
        }
    }
}
