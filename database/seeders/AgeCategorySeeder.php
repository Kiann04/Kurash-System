<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $ageCategories = [
            ['name' => 'Kids 4-7', 'min_age' => 4, 'max_age' => 7],
            ['name' => 'Kids 8-11', 'min_age' => 8, 'max_age' => 11],
            ['name' => 'Kids 12-13', 'min_age' => 12, 'max_age' => 13],
            ['name' => 'Cadets 14-15', 'min_age' => 14, 'max_age' => 15],
            ['name' => 'Juniors 16-17', 'min_age' => 16, 'max_age' => 17],
            ['name' => 'Adults 18+', 'min_age' => 18, 'max_age' => 99],
        ];

        DB::table('age_categories')->insert($ageCategories);
    }
}