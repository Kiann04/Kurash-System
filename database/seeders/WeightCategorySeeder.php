<?php

namespace Database\Seeders;

use App\Models\AgeCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeightCategorySeeder extends Seeder
{
    public function run(): void
    {
        $ageCategoryIds = AgeCategory::query()->pluck('id', 'name');

        $weightCategories = [
            // ===== Kids 4-7 =====
            ['name' => '-19', 'gender' => 'male', 'min_weight' => 0, 'max_weight' => 19, 'age_category' => 'Kids 4-7'],
            ['name' => '+19', 'gender' => 'male', 'min_weight' => 19, 'max_weight' => 999, 'age_category' => 'Kids 4-7'],
            ['name' => '-17', 'gender' => 'female', 'min_weight' => 0, 'max_weight' => 17, 'age_category' => 'Kids 4-7'],
            ['name' => '+17', 'gender' => 'female', 'min_weight' => 17, 'max_weight' => 999, 'age_category' => 'Kids 4-7'],

            // ===== Kids 8-11 =====
            ['name' => '-21', 'gender' => 'male', 'min_weight' => 0, 'max_weight' => 21, 'age_category' => 'Kids 8-11'],
            ['name' => '-25', 'gender' => 'male', 'min_weight' => 21, 'max_weight' => 25, 'age_category' => 'Kids 8-11'],
            ['name' => '-30', 'gender' => 'male', 'min_weight' => 25, 'max_weight' => 30, 'age_category' => 'Kids 8-11'],
            ['name' => '-33', 'gender' => 'male', 'min_weight' => 30, 'max_weight' => 33, 'age_category' => 'Kids 8-11'],
            ['name' => '+33', 'gender' => 'male', 'min_weight' => 33, 'max_weight' => 999, 'age_category' => 'Kids 8-11'],
            ['name' => '-20', 'gender' => 'female', 'min_weight' => 0, 'max_weight' => 20, 'age_category' => 'Kids 8-11'],
            ['name' => '-24', 'gender' => 'female', 'min_weight' => 20, 'max_weight' => 24, 'age_category' => 'Kids 8-11'],
            ['name' => '-27', 'gender' => 'female', 'min_weight' => 24, 'max_weight' => 27, 'age_category' => 'Kids 8-11'],
            ['name' => '+27', 'gender' => 'female', 'min_weight' => 27, 'max_weight' => 999, 'age_category' => 'Kids 8-11'],

            // ===== Kids 12-13 =====
            ['name' => '-36', 'gender' => 'male', 'min_weight' => 0, 'max_weight' => 36, 'age_category' => 'Kids 12-13'],
            ['name' => '-44', 'gender' => 'male', 'min_weight' => 36, 'max_weight' => 44, 'age_category' => 'Kids 12-13'],
            ['name' => '-52', 'gender' => 'male', 'min_weight' => 44, 'max_weight' => 52, 'age_category' => 'Kids 12-13'],
            ['name' => '-60', 'gender' => 'male', 'min_weight' => 52, 'max_weight' => 60, 'age_category' => 'Kids 12-13'],
            ['name' => '-65', 'gender' => 'male', 'min_weight' => 60, 'max_weight' => 65, 'age_category' => 'Kids 12-13'],
            ['name' => '-70', 'gender' => 'male', 'min_weight' => 65, 'max_weight' => 70, 'age_category' => 'Kids 12-13'],
            ['name' => '+70', 'gender' => 'male', 'min_weight' => 70, 'max_weight' => 999, 'age_category' => 'Kids 12-13'],
            ['name' => '-30', 'gender' => 'female', 'min_weight' => 0, 'max_weight' => 30, 'age_category' => 'Kids 12-13'],
            ['name' => '-36', 'gender' => 'female', 'min_weight' => 30, 'max_weight' => 36, 'age_category' => 'Kids 12-13'],
            ['name' => '-40', 'gender' => 'female', 'min_weight' => 36, 'max_weight' => 40, 'age_category' => 'Kids 12-13'],
            ['name' => '-44', 'gender' => 'female', 'min_weight' => 40, 'max_weight' => 44, 'age_category' => 'Kids 12-13'],
            ['name' => '-48', 'gender' => 'female', 'min_weight' => 44, 'max_weight' => 48, 'age_category' => 'Kids 12-13'],
            ['name' => '-52', 'gender' => 'female', 'min_weight' => 48, 'max_weight' => 52, 'age_category' => 'Kids 12-13'],
            ['name' => '-57', 'gender' => 'female', 'min_weight' => 52, 'max_weight' => 57, 'age_category' => 'Kids 12-13'],
            ['name' => '+57', 'gender' => 'female', 'min_weight' => 57, 'max_weight' => 999, 'age_category' => 'Kids 12-13'],

            // ===== Cadets 14-15 =====
            ['name' => '-46', 'gender' => 'male', 'min_weight' => 0, 'max_weight' => 46, 'age_category' => 'Cadets 14-15'],
            ['name' => '-50', 'gender' => 'male', 'min_weight' => 46, 'max_weight' => 50, 'age_category' => 'Cadets 14-15'],
            ['name' => '-55', 'gender' => 'male', 'min_weight' => 50, 'max_weight' => 55, 'age_category' => 'Cadets 14-15'],
            ['name' => '-60', 'gender' => 'male', 'min_weight' => 55, 'max_weight' => 60, 'age_category' => 'Cadets 14-15'],
            ['name' => '-65', 'gender' => 'male', 'min_weight' => 60, 'max_weight' => 65, 'age_category' => 'Cadets 14-15'],
            ['name' => '-71', 'gender' => 'male', 'min_weight' => 65, 'max_weight' => 71, 'age_category' => 'Cadets 14-15'],
            ['name' => '-77', 'gender' => 'male', 'min_weight' => 71, 'max_weight' => 77, 'age_category' => 'Cadets 14-15'],
            ['name' => '-83', 'gender' => 'male', 'min_weight' => 77, 'max_weight' => 83, 'age_category' => 'Cadets 14-15'],
            ['name' => '+83', 'gender' => 'male', 'min_weight' => 83, 'max_weight' => 999, 'age_category' => 'Cadets 14-15'],
            ['name' => '-40', 'gender' => 'female', 'min_weight' => 0, 'max_weight' => 40, 'age_category' => 'Cadets 14-15'],
            ['name' => '-44', 'gender' => 'female', 'min_weight' => 40, 'max_weight' => 44, 'age_category' => 'Cadets 14-15'],
            ['name' => '-48', 'gender' => 'female', 'min_weight' => 44, 'max_weight' => 48, 'age_category' => 'Cadets 14-15'],
            ['name' => '-52', 'gender' => 'female', 'min_weight' => 48, 'max_weight' => 52, 'age_category' => 'Cadets 14-15'],
            ['name' => '-57', 'gender' => 'female', 'min_weight' => 52, 'max_weight' => 57, 'age_category' => 'Cadets 14-15'],
            ['name' => '-63', 'gender' => 'female', 'min_weight' => 57, 'max_weight' => 63, 'age_category' => 'Cadets 14-15'],
            ['name' => '+63', 'gender' => 'female', 'min_weight' => 63, 'max_weight' => 999, 'age_category' => 'Cadets 14-15'],

            // ===== Juniors 16-17 =====
            ['name' => '-50', 'gender' => 'male', 'min_weight' => 0, 'max_weight' => 50, 'age_category' => 'Juniors 16-17'],
            ['name' => '-55', 'gender' => 'male', 'min_weight' => 50, 'max_weight' => 55, 'age_category' => 'Juniors 16-17'],
            ['name' => '-60', 'gender' => 'male', 'min_weight' => 55, 'max_weight' => 60, 'age_category' => 'Juniors 16-17'],
            ['name' => '-65', 'gender' => 'male', 'min_weight' => 60, 'max_weight' => 65, 'age_category' => 'Juniors 16-17'],
            ['name' => '-71', 'gender' => 'male', 'min_weight' => 65, 'max_weight' => 71, 'age_category' => 'Juniors 16-17'],
            ['name' => '-77', 'gender' => 'male', 'min_weight' => 71, 'max_weight' => 77, 'age_category' => 'Juniors 16-17'],
            ['name' => '-83', 'gender' => 'male', 'min_weight' => 77, 'max_weight' => 83, 'age_category' => 'Juniors 16-17'],
            ['name' => '-90', 'gender' => 'male', 'min_weight' => 83, 'max_weight' => 90, 'age_category' => 'Juniors 16-17'],
            ['name' => '+90', 'gender' => 'male', 'min_weight' => 90, 'max_weight' => 999, 'age_category' => 'Juniors 16-17'],
            ['name' => '-44', 'gender' => 'female', 'min_weight' => 0, 'max_weight' => 44, 'age_category' => 'Juniors 16-17'],
            ['name' => '-48', 'gender' => 'female', 'min_weight' => 44, 'max_weight' => 48, 'age_category' => 'Juniors 16-17'],
            ['name' => '-52', 'gender' => 'female', 'min_weight' => 48, 'max_weight' => 52, 'age_category' => 'Juniors 16-17'],
            ['name' => '-57', 'gender' => 'female', 'min_weight' => 52, 'max_weight' => 57, 'age_category' => 'Juniors 16-17'],
            ['name' => '-63', 'gender' => 'female', 'min_weight' => 57, 'max_weight' => 63, 'age_category' => 'Juniors 16-17'],
            ['name' => '-70', 'gender' => 'female', 'min_weight' => 63, 'max_weight' => 70, 'age_category' => 'Juniors 16-17'],
            ['name' => '+70', 'gender' => 'female', 'min_weight' => 70, 'max_weight' => 999, 'age_category' => 'Juniors 16-17'],

            // ===== Adults 18+ =====
            ['name' => '-44', 'gender' => 'female', 'min_weight' => 0, 'max_weight' => 44, 'age_category' => 'Adults 18+'],
            ['name' => '-48', 'gender' => 'female', 'min_weight' => 44, 'max_weight' => 48, 'age_category' => 'Adults 18+'],
            ['name' => '-52', 'gender' => 'female', 'min_weight' => 48, 'max_weight' => 52, 'age_category' => 'Adults 18+'],
            ['name' => '-57', 'gender' => 'female', 'min_weight' => 52, 'max_weight' => 57, 'age_category' => 'Adults 18+'],
            ['name' => '-63', 'gender' => 'female', 'min_weight' => 57, 'max_weight' => 63, 'age_category' => 'Adults 18+'],
            ['name' => '-70', 'gender' => 'female', 'min_weight' => 63, 'max_weight' => 70, 'age_category' => 'Adults 18+'],
            ['name' => '-78', 'gender' => 'female', 'min_weight' => 70, 'max_weight' => 78, 'age_category' => 'Adults 18+'],
            ['name' => '-87', 'gender' => 'female', 'min_weight' => 78, 'max_weight' => 87, 'age_category' => 'Adults 18+'],
            ['name' => '+87', 'gender' => 'female', 'min_weight' => 87, 'max_weight' => 999, 'age_category' => 'Adults 18+'],
            ['name' => '-60', 'gender' => 'male', 'min_weight' => 0, 'max_weight' => 60, 'age_category' => 'Adults 18+'],
            ['name' => '-66', 'gender' => 'male', 'min_weight' => 60, 'max_weight' => 66, 'age_category' => 'Adults 18+'],
            ['name' => '-73', 'gender' => 'male', 'min_weight' => 66, 'max_weight' => 73, 'age_category' => 'Adults 18+'],
            ['name' => '-81', 'gender' => 'male', 'min_weight' => 73, 'max_weight' => 81, 'age_category' => 'Adults 18+'],
            ['name' => '-90', 'gender' => 'male', 'min_weight' => 81, 'max_weight' => 90, 'age_category' => 'Adults 18+'],
            ['name' => '-100', 'gender' => 'male', 'min_weight' => 90, 'max_weight' => 100, 'age_category' => 'Adults 18+'],
            ['name' => '+100', 'gender' => 'male', 'min_weight' => 100, 'max_weight' => 999, 'age_category' => 'Adults 18+'],
        ];

        foreach ($weightCategories as $category) {
            $ageCategoryId = $ageCategoryIds[$category['age_category']] ?? null;

            if (!$ageCategoryId) {
                continue;
            }

            DB::table('weight_categories')->updateOrInsert(
                [
                    'name' => $category['name'],
                    'gender' => $category['gender'],
                    'age_category_id' => $ageCategoryId,
                ],
                [
                    'min_weight' => $category['min_weight'],
                    'max_weight' => $category['max_weight'],
                ]
            );
        }
    }
}
