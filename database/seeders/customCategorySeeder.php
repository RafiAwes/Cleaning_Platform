<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{Category, customCategory};

class customCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = array(
            'Less than 100 sq feet',
            '100 to 200 sq feet',
            '200 to 300 sq feet',
            '300 to 400 sq feet',
            '400 to 500 sq feet',
            'More than 500 sq feet'
        );

        // Get all categories from the database
        $categories = Category::all();

        foreach ($categories as $category) {
            foreach ($options as $option) {
                customCategory::create([
                    'category_id' => $category->id,
                    'name' => $category->name,
                    'option' => $option,
                ]);
            }
        }
    }
}
