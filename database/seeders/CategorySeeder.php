<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = array('Appartment Cleaning','Home Cleaning', 'Kitchen Cleaning', 'Bathroom Cleaning', 'Yard Cleaning');

        foreach ($category as $cat) {
            Category::create([
                'name' => $cat,
                'image' => 'images/category/default.jpg',
            ]);
        }
    }
}
